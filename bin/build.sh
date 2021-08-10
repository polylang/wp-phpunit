#!/bin/sh
# Builds the project (Composer, npm).
#
# $1 string Whether to run composer install or update. '-u' or '--update' to update, anything else to install.
# $2 int    1 to run npm. 0 otherwize.

COMPOSER_COMMAND='install'

if [[ "$1" = "-u" ]] || [[ "$1" = "--update" ]]; then
	COMPOSER_COMMAND='update'
fi

echo "Installing PHP packages..."
composer $COMPOSER_COMMAND # Need update to ensure to have the latest version of the dependencies.

if [[ $2 ]]; then
	echo "Running build..."
	npm install && npm run build # minify js and css files.
fi

echo "Build done!"

echo "Cleanup." # Discard composer.lock and package-lock.json changings to ensure they'll never be pushed on repository.
git checkout -- composer.lock package-lock.json
