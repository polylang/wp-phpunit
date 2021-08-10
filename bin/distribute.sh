#!/bin/bash
# Creates a zip file of the project.
#
# $1 string Plugin slug. Ex: 'polylang'.
# $2 int    1 to run npm. 0 otherwize.

rm -rf vendor/ # Make sure to remove all traces of development dependencies.

echo "Installing PHP packages..."
composer update --no-dev # composer.lock file is always present because pushed on repository.

if [[ $2 ]]; then
	echo "Running build..."
	npm install && npm run build # minify js and css files.
fi

rsync -rc --exclude-from=.distignore . "$1/" --delete --delete-excluded
zip -r "$1.zip" "$1/*"
rm -rf "$1/"
