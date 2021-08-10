#!/usr/bin/env bash

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")
DOWNLOADS_DIR="$TMPDIR/downloads"

PARENT_DIR=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

WORKING_DIR="$PWD"
DEPS_DIR="$WORKING_DIR/tmp"
WP_PLUGINS_DIR="$DEPS_DIR/plugins"
WP_THEMES_DIR="$DEPS_DIR/themes"

# Colors used to print messages to stdout.
if [[ "$(type -t tput)" == 'file' ]] && [[ $(tput colors) ]]; then
	INFO_C="$(tput setaf 6)"
	SUCCESS_C="$(tput setaf 2)"
	ERROR_C="$(tput setaf 1)"
	NO_C="$(tput sgr0)"
else
	INFO_C='\033[0;36m'
	SUCCESS_C='\033[0;32m'
	ERROR_C='\033[0;31m'
	NO_C='\033[0m'
fi

# Include download tools.
. "$PARENT_DIR/generic-download-tools.sh"

# Create the downloads folder.
mkdir -p $DOWNLOADS_DIR
mkdir -p $DEPS_DIR

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
	if [[ $VERSION == 'trunk' ]]; then
		download https://downloads.wordpress.org/plugin/$PLUGIN_SLUG.zip $DOWNLOADS_DIR/$PLUGIN_SLUG.zip
	else
		download https://downloads.wordpress.org/plugin/$PLUGIN_SLUG.$VERSION.zip $DOWNLOADS_DIR/$PLUGIN_SLUG.zip
	fi

	unzip -q $DOWNLOADS_DIR/$PLUGIN_SLUG.zip -d $WP_PLUGINS_DIR

	if [[ $? == 0 ]] ; then
		messageSuccessfullyInstalled "$PLUGIN_SLUG $VERSION"
	else
		messageInstallationFailure "$PLUGIN_SLUG $VERSION"
	fi ;
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
site $PLUGIN_SLUG:{YOUR-SITE}"
		return
	fi

	local LICENSE=$(getLicenseCode $PLUGIN_SLUG)

	if [[ ! $LICENSE ]]; then
		# No license key.
		formatMessage "${ERROR_C}No license key found for $PLUGIN_SLUG in the file LICENSE-CODES.${NO_C}"
		return
	fi

	local SITE=$(getLicenseSite $PLUGIN_SLUG)
	local PARAMS="edd_action=get_version&license=$LICENSE&url=$SITE&item_name=$PLUGIN_NAME&version=0.1"
	local HEADERS='Content-Type: application/x-www-form-urlencoded;Cache-Control: no-cache;Accept: application/json;Connection: keep-alive'

	if [[ `which curl` ]]; then
		local INFO=$(curl -d "$PARAMS" -H "$HADERS" -X POST -s "$URL")
	elif [[ `which wget` ]]; then
		local INFO=$(wget --post-data "$PARAMS" --header "$HADERS" "$URL" -q -O -)
	fi

	local URL=$(getPackageUrl "$INFO")

	if [[ ! $URL ]]; then
		# Could not get the package URL.
		formatMessage "${ERROR_C}Could not get $PLUGIN_SLUG's package URL.${NO_C}"
		return
	fi

	# Download the plugin.
	download $URL $DOWNLOADS_DIR/$PLUGIN_SLUG.zip
	unzip -q $DOWNLOADS_DIR/$PLUGIN_SLUG.zip -d $WP_PLUGINS_DIR

	local VERSION=$(getPackageVersion $INFO)

	if [[ $? == 0 ]] ; then
		messageSuccessfullyInstalled "$PLUGIN_SLUG $VERSION"
	else
		messageInstallationFailure "$PLUGIN_SLUG $VERSION"
	fi ;
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

	if [[ $PLLWC_VERSION == 'dev' ]]; then
		git checkout trunk
	else
		git checkout $PLLWC_VERSION
	fi

	composer install --no-dev

	if [[ ! $PLLWC_VERSION ]]; then
		PLLWC_VERSION=$(getVersionFromPluginFile $PLUGIN_FILE)
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

	if [[ $PLL_VERSION == 'dev' ]]; then
		git checkout master
		composer install --no-dev
	else
		git checkout $PLL_VERSION
		# `composer install` if the version is greater than or equal to 2.8.
		if [[ ! $PLL_VERSION ]] || [[ "$(printf '%s\n' "$PLL_VERSION" "2.8" | sort -V | head -n1)" = "2.8" ]]; then
			composer install --no-dev
		fi
	fi

	if [[ ! $PLL_VERSION ]]; then
		PLL_VERSION=$(getVersionFromPluginFile $PLUGIN_FILE)
	fi

	messageSuccessfullyInstalled "$PLUGIN_DIR $PLL_VERSION"
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

	download https://downloads.wordpress.org/theme/$THEME_SLUG.zip $DOWNLOADS_DIR/$THEME_SLUG.zip
	unzip -q $DOWNLOADS_DIR/$THEME_SLUG.zip -d $WP_THEMES_DIR

	local VERSION=$(getVersionFromThemeFile $THEME_SLUG)

	if [[ $? == 0 ]] ; then
		messageSuccessfullyInstalled "$THEME_SLUG $VERSION"
	else
		messageInstallationFailure "$THEME_SLUG $VERSION"
	fi ;
}

# Returns the license code for the given plugin or theme.
# The code is fetched from the `LICENSE-CODES` file.
#
# Globals: WORKING_DIR
#
# $1     string A plugin or theme slug.
# return string
getLicenseCode() {
	local LICENSE=$(grep -Eo "license $1:[a-f0-9]+" $WORKING_DIR/LICENSE-CODES)
	echo ${LICENSE/license $1:}
}

# Returns the site URL registered with the license for the given plugin or theme.
# The site URL is fetched from the `LICENSE-CODES` file.
#
# Globals: WORKING_DIR
#
# $1     string A plugin or theme slug.
# return string
getLicenseSite() {
	local SITE=$(grep -Eo "site $1:[^[:space:]]+" $WORKING_DIR/LICENSE-CODES)
	echo ${SITE/site $1:}
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

# Formats a message to be printed.
#
# $1     string The message.
# return string
formatMessage() {
	echo -e "  - $1"
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
