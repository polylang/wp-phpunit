#!/bin/bash
# Creates a zip file of the project.
#
# $1 string A custom plugin slug. Optional.
# $2 string '--no-npm' to not run npm.

echo "Installing PHP packages..."

# Include color values (must be done before `rm -rf vendor`).
PARENT_DIR=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )
. "$PARENT_DIR/colors.sh"

# Make sure to remove all traces of development dependencies.
rm -rf vendor
# Update/Install to ensure to have the latest version of the dependencies.
if [[ -e "composer.lock" ]]; then
	composer update --no-dev
else
	composer install --no-dev
fi

if [[ $1 ]]; then
	PACKAGE_NAME=$1
else
	PACKAGE_NAME=$(basename -s .git `git config --get remote.origin.url`)
fi

if [[ '--no-npm' != $2 ]]; then
	echo "Running build..."
	# minify js and css files.
	npm install --package-lock-only && npm ci && npm run build
fi

echo "Creating archive file..."
rsync -rc --delete --delete-excluded --exclude-from='.distignore' . "$PACKAGE_NAME"
zip -r "${PACKAGE_NAME}.zip" "$PACKAGE_NAME"
rm -rf "$PACKAGE_NAME"

echo "${SUCCESS_C}Zip file created!${NO_C}"
