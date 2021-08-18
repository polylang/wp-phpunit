#!/bin/bash
# Creates a zip file of the project.
#
# $1 string A custom plugin slug. Optional.
# $2 string '--no-npm' to not run npm.

rm -rf vendor/ # Make sure to remove all traces of development dependencies.

echo "Installing PHP packages..."
composer update --no-dev # composer.lock file is always present because pushed on repository.

if [[ $1 ]]; then
	local PACKAGE_NAME=$1
else
	local PACKAGE_NAME=$(basename -s .git `git config --get remote.origin.url`)
fi

if [[ '--no-npm' != $2 ]]; then
	echo "Running build..."
	npm install && npm run build # minify js and css files.
fi

rsync -rc --exclude-from=.distignore . "$PACKAGE_NAME/" --delete --delete-excluded
zip -r "$PACKAGE_NAME.zip" "$PACKAGE_NAME/*"
rm -rf "$PACKAGE_NAME/"
