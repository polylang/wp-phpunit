#!/bin/sh
# Builds the project (Composer, npm).
#
# $1 string Whether to run composer install or update. '-u' or '--update' to update, anything else to install.
# $2 string '--no-npm' to not run npm.

echo "Installing PHP packages..."

# Include color values (must be done before `rm -rf vendor`).
PARENT_DIR=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )
. "$PARENT_DIR/colors.sh"

# Make sure to remove all traces of development dependencies.
rm -rf vendor
# Update/Install to ensure to have the latest version of the dependencies.
if [[ "$1" = "-u" ]] || [[ "$1" = "--update" ]]; then
	composer update
else
	composer install
fi

if [[ '--no-npm' != $2 ]]; then
	echo "Running build..."
	# Minify js and css files.
	npm install && npm run build
fi

echo "${SUCCESS_C}Build done!${NO_C}"
