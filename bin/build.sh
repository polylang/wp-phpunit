#!/bin/sh
# Builds the project (Composer, npm).
#
# $1 string Whether to run composer install or update. '-u' or '--update' to update, anything else to install.
# $2 string '--no-npm' to not run npm.

if [[ "$1" = "-u" ]] || [[ "$1" = "--update" ]]; then
	local COMPOSER_COMMAND='update'
else
	local COMPOSER_COMMAND='install'
fi

echo "Installing PHP packages..."
rm -rf vendor/ # Make sure to remove all traces of development dependencies.
composer $COMPOSER_COMMAND # Need update to ensure to have the latest version of the dependencies.

if [[ '--no-npm' != $2 ]]; then
	echo "Running build..."
	npm install && npm run build # minify js and css files.
fi

echo "Build done!"

echo "Cleanup." # Discard composer.lock and package-lock.json changings to ensure they'll never be pushed on repository.
git checkout -- composer.lock package-lock.json
