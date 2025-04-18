#!/usr/bin/env bash

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo "$TMPDIR" | sed -e "s/\/$//")
DOWNLOADS_DIR="$TMPDIR/downloads"

PARENT_DIR=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

WORKING_DIR="$PWD"
DEPS_DIR="$WORKING_DIR/tmp"
WP_PLUGINS_DIR="$DEPS_DIR/plugins"
WP_THEMES_DIR="$DEPS_DIR/themes"

# Include tools.
. "$PARENT_DIR/tools.sh"

# Create the downloads folder.
mkdir -p "$DOWNLOADS_DIR"
mkdir -p "$DEPS_DIR"

# Downloads a plugin from the repository.
# Ex: downloadPluginFromRepository woocommerce
# Writes to stdout whether the plugin has been installed or not.
#
# Globals: DOWNLOADS_DIR, WP_PLUGINS_DIR
#
# $1 string The plugin slug.
# $2 int    1 to force download the trunk version.
downloadPluginFromRepository() {
	local PLUGIN_SLUG="$1"
	local DL_TRUNK="$2"

	if [[ -f "$WP_PLUGINS_DIR/$PLUGIN_SLUG/$PLUGIN_SLUG.php" ]]; then
		# The plugin exists.
		messageAlreadyInstalled "$PLUGIN_SLUG"
		return
	fi

	if [[ $DL_TRUNK ]]; then
		# Force plugin in trunk.
		local VERSION='trunk'
	else
		# Get the version from readme.txt.
		local VERSION=$(getContent https://plugins.svn.wordpress.org/$PLUGIN_SLUG/trunk/readme.txt | grep -i 'Stable tag:' | grep -Eo '[0-9.]+$')
	fi

	# Download the plugin.
	local ZIP_PATH="$DOWNLOADS_DIR/$PLUGIN_SLUG.zip"

	if [[ $VERSION == 'trunk' ]]; then
		download https://downloads.wordpress.org/plugin/$PLUGIN_SLUG.zip "$ZIP_PATH"
	else
		download https://downloads.wordpress.org/plugin/$PLUGIN_SLUG.$VERSION.zip "$ZIP_PATH"
	fi

	maybeUnzip "$ZIP_PATH" "$WP_PLUGINS_DIR" "$PLUGIN_SLUG $VERSION"
}

# Downloads a plugin distributed by a EDD install.
# Writes to stdout whether the plugin has been installed or not.
# Ex: download_plugin_from_edd wp-all-import-pro 'WP All Import' https://www.wpallimport.com
# Also, the plugin requires a license code, it must be provided by creating a `LICENSE-CODES` file at the root of the
# project. This file must contain the license code and the registered site (the site may not be required) with this
# format:
# license {PLUGIN-SLUG}:{YOUR-LICENSE}
# site {PLUGIN-SLUG}:{YOUR-SITE}
#
# Globals: DOWNLOADS_DIR, ERROR_C, NO_C, WP_PLUGINS_DIR
#
# $1 string The plugin slug.
# $2 string The plugin's name.
# $3 string The base URL to download the plugin from.
downloadPluginFromEdd() {
	local PLUGIN_SLUG="$1"
	local PLUGIN_NAME="$2"
	local URL="$3"

	if [[ -f "$WP_PLUGINS_DIR/$PLUGIN_SLUG/$PLUGIN_SLUG.php" ]]; then
		# The plugin exists.
		messageAlreadyInstalled "$PLUGIN_SLUG"
		return
	fi

	if [[ ! -f "$WORKING_DIR/LICENSE-CODES" ]]; then
		# No license code.
		formatMessage "${ERROR_C}$PLUGIN_SLUG's license not provided.${NO_C} Please create a file named LICENSE-CODES containing the license code and the registered site (the site may not be required) with this format:
license $PLUGIN_SLUG:{YOUR-LICENSE}
site $PLUGIN_SLUG:{YOUR-SITE}
If $PLUGIN_SLUG doesn't require a license key, do the following:
license $PLUGIN_SLUG:none"
		return
	fi

	local LICENSE=$(getLicenseValue "license $PLUGIN_SLUG")

	if [[ ! $LICENSE ]]; then
		# No license key.
		formatMessage "${ERROR_C}No license key found for $PLUGIN_SLUG in the file LICENSE-CODES.${NO_C}"
		return
	fi

	if [[ 'none' == $LICENSE ]]; then
		# Allow to get a plugin that doesn't require a license key.
		LICENSE=''
	fi

	local SITE=$(getLicenseValue "site $PLUGIN_SLUG")
	local PARAMS="edd_action=get_version&license=$LICENSE&url=$SITE&item_name=$PLUGIN_NAME&version=0.1"
	local HEADERS='Content-Type: application/x-www-form-urlencoded;Cache-Control: no-cache;Accept: application/json;Connection: keep-alive'

	if [[ `which curl` ]]; then
		local INFO=$(curl -d "$PARAMS" -H "$HEADERS" -X POST -s "$URL")
	elif [[ `which wget` ]]; then
		local INFO=$(wget --post-data "$PARAMS" --header "$HEADERS" "$URL" -q -O -)
	fi

	local URL=$(getPackageUrl "$INFO")

	if [[ ! $URL ]]; then
		# Could not get the package URL.
		local MSG=$(getPackageMsg "$INFO")
		formatMessage "${ERROR_C}Could not get $PLUGIN_SLUG's package URL:${NO_C} $MSG"
		return
	fi

	# Download the plugin.
	local ZIP_PATH="$DOWNLOADS_DIR/$PLUGIN_SLUG.zip"
	local VERSION=$(getPackageVersion "$INFO")

	download "$URL" "$ZIP_PATH"

	maybeUnzip "$ZIP_PATH" "$WP_PLUGINS_DIR" "$PLUGIN_SLUG $VERSION"
}

# Downloads Polylang for WooCommerce.
# Writes to stdout whether the plugin has been installed or not.
#
# Globals: PLLWC_VERSION, WORKING_DIR, WP_PLUGINS_DIR
downloadPolylangForWoocommerce() {
	local PLUGIN_DIR="polylang-wc"
	local PLUGIN_FILE="$PLUGIN_DIR/polylang-wc.php"

	if [[ -f "$WP_PLUGINS_DIR/$PLUGIN_FILE" ]]; then
		# The plugin exists.
		messageAlreadyInstalled "$PLUGIN_DIR"
		return
	fi

	git clone https://github.com/polylang/polylang-wc.git "$WP_PLUGINS_DIR/$PLUGIN_DIR"
	cd "$WP_PLUGINS_DIR/$PLUGIN_DIR"

	if [[ ! $PLLWC_VERSION ]] || [[ $PLLWC_VERSION == 'dev' ]]; then
		git checkout trunk
	else
		git checkout $PLLWC_VERSION
	fi

	composer install --no-dev

	if [[ ! $PLLWC_VERSION ]]; then
		PLLWC_VERSION=$(getVersionFromPluginFile "$PLUGIN_FILE")
	fi

	messageSuccessfullyInstalled "$PLUGIN_DIR $PLLWC_VERSION"
	cd "$WORKING_DIR"
}

# Downloads Polylang Pro.
# Writes to stdout whether the plugin has been installed or not.
#
# Globals: PLL_VERSION, WORKING_DIR, WP_PLUGINS_DIR
downloadPolylangPro() {
	local PLUGIN_DIR="polylang-pro"
	local PLUGIN_FILE="$PLUGIN_DIR/polylang.php"

	if [[ -f "$WP_PLUGINS_DIR/$PLUGIN_FILE" ]]; then
		# The plugin exists.
		messageAlreadyInstalled "$PLUGIN_DIR"
		return
	fi

	git clone https://github.com/polylang/polylang-pro.git "$WP_PLUGINS_DIR/$PLUGIN_DIR"
	cd "$WP_PLUGINS_DIR/$PLUGIN_DIR"

	if [[ ! $PLL_VERSION ]] || [[ $PLL_VERSION == 'dev' ]]; then
		git checkout master
		composer install --no-dev
	else
		git checkout $PLL_VERSION
		# `composer install` if the version is greater than or equal to 2.8.
		if [[ "$(printf '%s\n' "$PLL_VERSION" "2.8" | sort -V | head -n1)" = "2.8" ]]; then
			composer install --no-dev
		fi
	fi

	if [[ ! $PLL_VERSION ]]; then
		PLL_VERSION=$(getVersionFromPluginFile "$PLUGIN_FILE")
	fi

	messageSuccessfullyInstalled "$PLUGIN_DIR $PLL_VERSION"
	cd "$WORKING_DIR"
}

# Downloads WooCommerce.
# Writes to stdout whether the plugin has been installed or not.
#
# Globals: WC_VERSION, WORKING_DIR, WP_PLUGINS_DIR
downloadWoocommerce() {
	local PLUGIN_DIR="woocommerce"
	local PLUGIN_FILE="$PLUGIN_DIR/woocommerce.php"

	if [[ -f "$WP_PLUGINS_DIR/$PLUGIN_FILE" ]]; then
		# The plugin exists.
		messageAlreadyInstalled "$PLUGIN_DIR"
		return
	fi

	if [[ ${WC_VERSION:0:1} == "4" || ${WC_VERSION:0:1} == "5" ]]; then
		# Backward compatibility with WC < 6.0.
		local MOVE=0
		local TARGET_DIR="$PLUGIN_DIR"
	else
		#local PLUGIN_DIR="$PLUGIN_DIR/plugins/$PLUGIN_DIR"
		local MOVE=1
		local TARGET_DIR="wootmp"
	fi

	git clone https://github.com/woocommerce/woocommerce.git "$WP_PLUGINS_DIR/$TARGET_DIR"
	cd "$WP_PLUGINS_DIR/$TARGET_DIR"

	if [[ ! $WC_VERSION ]] || [[ $WC_VERSION == 'dev' ]]; then
		git checkout trunk
		php bin/generate-feature-config.php
	else
		git checkout $WC_VERSION

		if [[ ${WC_VERSION:0:1} == "6" && ${WC_VERSION:2:1} > "4" ]]; then
			php bin/generate-feature-config.php
		fi
	fi

	if [[ $MOVE ]]; then
		mv "$WP_PLUGINS_DIR/$TARGET_DIR/plugins/$PLUGIN_DIR" "$WP_PLUGINS_DIR"
		rm -rf "$WP_PLUGINS_DIR/$TARGET_DIR"
		cd "$WP_PLUGINS_DIR/$PLUGIN_DIR"
	fi

	composer install --no-dev

	if [[ ! $WC_VERSION ]]; then
		WC_VERSION=$(getVersionFromPluginFile "$PLUGIN_FILE")
	fi

	messageSuccessfullyInstalled "$PLUGIN_DIR $WC_VERSION"
	cd "$WORKING_DIR"
}

# Downloads Gutenberg.
# Writes to stdout whether the plugin has been installed or not.
#
# Globals: WORKING_DIR, WP_PLUGINS_DIR
#
# $1 string The version to install.
downloadGutenberg() {
	local VERSION="$1";
	local PLUGIN_DIR="gutenberg"
	local PLUGIN_FILE="$PLUGIN_DIR/gutenberg.php"

	if [[ -f "$WP_PLUGINS_DIR/$PLUGIN_FILE" ]]; then
		# The plugin exists.
		messageAlreadyInstalled "$PLUGIN_DIR"
		return
	fi

	git clone https://github.com/WordPress/gutenberg.git "$WP_PLUGINS_DIR/$PLUGIN_DIR"
	cd "$WP_PLUGINS_DIR/$PLUGIN_DIR"

	# 7.5.0 is the version included in WordPress 5.4 - See https://make.wordpress.org/core/2020/02/12/whats-new-in-gutenberg-12-february/.
	git checkout tags/v$VERSION -b v$VERSION

	messageSuccessfullyInstalled "$PLUGIN_SLUG $VERSION"
	cd "$WORKING_DIR"
}

# Downloads a theme from the repository.
# Writes to stdout whether the theme has been installed or not.
#
# Globals: DOWNLOADS_DIR, WP_THEMES_DIR
#
# $1 string The theme slug.
downloadThemeFromRepository() {
	local THEME_SLUG="$1"

	if [[ -f "$WP_THEMES_DIR/$THEME_SLUG/style.css" ]]; then
		# The theme exists.
		messageAlreadyInstalled "$THEME_SLUG"
		return
	fi

	# Download the plugin.
	local ZIP_PATH="$DOWNLOADS_DIR/$THEME_SLUG.zip"

	download https://downloads.wordpress.org/theme/$THEME_SLUG.zip "$ZIP_PATH"

	if [[ ! -f "$ZIP_PATH" ]]; then
		messageInstallationFailure "$THEME_SLUG"
		return
	fi

	unzip -q "$ZIP_PATH" -d "$WP_THEMES_DIR"

	if [[ ! -f "$WP_THEMES_DIR/$THEME_SLUG/style.css" ]]; then
		messageInstallationFailure "$THEME_SLUG"
		return
	fi

	local VERSION=$(getVersionFromThemeFile "$THEME_SLUG")

	if [[ $? == 0 ]] ; then
		messageSuccessfullyInstalled "$THEME_SLUG $VERSION"
	else
		messageInstallationFailure "$THEME_SLUG $VERSION"
	fi ;
}

# Returns the value of the given license data.
# The data are fetched from the `LICENSE-CODES` file.
#
# Globals: WORKING_DIR
#
# $1     string A license data name.
# return string
getLicenseValue() {
	echo $(grep -Eo "^$1[[:space:]]*:[[:space:]]*[^[:space:]]+[[:space:]]*$" $WORKING_DIR/LICENSE-CODES | sed -e "s/^$1[[:space:]]*:[[:space:]]*//" -e 's/[[:space:]]*$//')
}

# Returns the package message from the given contents.
#
# $1     string Some contents.
# return string
getPackageMsg() {
	echo "$1" | grep -Eo '"msg":".+?"' | sed -e 's/^"msg":"//' -e 's/"$//'
}

# Returns the package URL from the given contents.
#
# $1     string Some contents.
# return string
getPackageUrl() {
	local URL=$(echo "$1" | grep -Eo '"package":.*?[^\\]"' | grep -Eo 'http.+[^"]')
	echo ${URL//\\\//\/}
}

# Returns the package version from the given contents.
#
# $1     string Some contents.
# return string
getPackageVersion() {
	echo "$1" | grep -Eo '"stable_version":"[0-9.]+"' | grep -Eo '[0-9.]+'
}

# Returns the version of the given plugin.
# The version is fetched from the plugin's main file.
#
# Globals: WP_PLUGINS_DIR
#
# $1     string Path to the plugin's main file, relative to the WP's plugins folder.
# return string
getVersionFromPluginFile() {
	echo $(grep -Eo " \* Version:[[:space:]]+[^[:space:]]+" "$WP_PLUGINS_DIR/$1" | grep -Eo '[^ ]+$')
}

# Returns the version of the given theme.
# The version is fetched from the theme's main CSS file.
#
# Globals: WP_THEMES_DIR
#
# $1     string The theme slug.
# return string
getVersionFromThemeFile() {
	local VERSION=$(grep -i "^[ \t/*#@]*Version:" "$WP_THEMES_DIR/$1/style.css" | grep -Eo '[0-9]+(\..+)?$')
	echo ${VERSION/ }
}

# Formats a "already installed" message to be printed for the given dependency.
#
# Globals: INFO_C, NO_C
#
# $1     string The dependency slug.
# return string
messageAlreadyInstalled() {
	formatMessage "${INFO_C}$1${NO_C} is already installed."
}

# Formats a "successfully installed" message to be printed for the given dependency.
#
# Globals: NO_C, SUCCESS_C
#
# $1     string The dependency slug.
# return string
messageSuccessfullyInstalled() {
	formatMessage "${SUCCESS_C}$1${NO_C} installed successfully."
}

# Formats a "installation failure" message to be printed for the given dependency.
#
# Globals: ERROR_C, NO_C
#
# $1     string The dependency slug.
# return string
messageInstallationFailure() {
	formatMessage "${ERROR_C}$1${NO_C} installation failure."
}

# Unzips a file.
# Writes to stdout whether the operation has been successful or not.
#
# $1 string The path to the zip file.
# $2 string The path to the destination folder.
# $3 string The name of the package being unzipped (usually "{slug} {version}").
maybeUnzip() {
	local ZIP_PATH="$1"
	local DESTINATION_PATH="$2"
	local NAME="$3"

	if [[ ! -f "$ZIP_PATH" ]]; then
		messageInstallationFailure "$NAME"
		return
	fi

	unzip -q "$ZIP_PATH" -d "$DESTINATION_PATH"

	if [[ $? == 0 ]] ; then
		messageSuccessfullyInstalled "$NAME"
	else
		messageInstallationFailure "$NAME"
	fi ;
}