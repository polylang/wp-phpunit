#!/usr/bin/env bash

# Is cmd command available ? Windows only.
if [ -x "$(command -v cmd)" ]; then
	# pwd -W option available in Git bash for windows.
	# We get current folder path with slash as separator.
	WORKING_DIR=$(pwd -W)
else
	WORKING_DIR="$PWD"
fi

PARENT_DIR=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )
WP_CORE_DIR="$WORKING_DIR/tmp/wordpress"
WP_TESTS_DIR="$WORKING_DIR/tmp/wordpress-tests-lib"

# Include color values.
. "$PARENT_DIR/colors.sh"

# Installs the WordPress test suite by using a local config file.
# Ex: installWpSuite latest true
# This requires a local config file named `DB-CONFIG` and located at the root of the project. This file must contain the
# data required to connect to the database in this format:
# db_host: {DB-HOST}
# db_name: {DB-NAME}
# db_user: {DB-USER}
# db_pass: {DB-PASS}
# If the file is missing or if data are missing, the following default values are used:
# db_host: localhost
# db_name: wordpress_tests
# db_user: root
# db_pass: '' (an empty string)
#
# Globals: PARENT_DIR, WP_CORE_DIR, $WP_TESTS_DIR
#
# $1 string The WordPress version to install. Default 'latest'.
# $2 string Whether to install the database or not: 'true' or 'false'. Default 'false'.
installWpSuite() {
	rm -rf "$WP_CORE_DIR/"
	rm -rf "$WP_TESTS_DIR/"

	local WP_VERSION='latest'
	local SKIP_DB_CREATION='true'

	if [[ -f "$WORKING_DIR/DB-CONFIG" ]]; then
		local DB_HOST=$(getConfigValue 'db_host' 'localhost')
		local DB_NAME=$(getConfigValue 'db_name' 'wordpress_tests')
		local DB_USER=$(getConfigValue 'db_user' 'root')
		local DB_PASS=$(getConfigValue 'db_pass' '')
	else
		local DB_HOST='localhost'
		local DB_NAME='wordpress_tests'
		local DB_USER='root'
		local DB_PASS=''
	fi

	if [[ $1 ]]; then
		WP_VERSION=$1
	fi

	if [[ 'true' == $2 ]]; then
		SKIP_DB_CREATION='false'
	fi

	echo -e "Installing WordPress test suite with the following config:
  - DB host: ${INFO_C}${DB_HOST}${NO_C}
  - DB name: ${INFO_C}${DB_NAME}${NO_C}
  - DB user: ${INFO_C}${DB_USER}${NO_C}
  - DB pass: ${INFO_C}${DB_PASS}${NO_C}
  - WP version: ${INFO_C}${WP_VERSION}${NO_C}
  - Skip DB creation: ${INFO_C}${SKIP_DB_CREATION}${NO_C}"
	. "$PARENT_DIR/install-wp-tests.sh" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST" "$WP_VERSION" "$SKIP_DB_CREATION"
}

# Returns the value of the given config name.
# The data are fetched from the `DB-CONFIG` file.
#
# Globals: WORKING_DIR
#
# $1     string A config name.
# $2     string A default value to use when the config is not found in the file. Optional.
# return string
getConfigValue() {
	local CONF=$(grep -Eo "^$1[[:space:]]*:[[:space:]]*[^[:space:]]+[[:space:]]*$" $WORKING_DIR/DB-CONFIG | sed -e "s/^$1[[:space:]]*:[[:space:]]*//" -e 's/[[:space:]]*$//')

	if [[ ! $CONF ]] && [[ $# -ge 2 ]]; then
		echo $2
	fi

	echo $CONF
}

installWpSuite $1 $2
