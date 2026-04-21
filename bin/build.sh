#!/usr/bin/env bash

# Builds the project (Composer, npm).
#
# $1 string Whether to run composer install or update. '-u' or '--update' to run `composer update`
#     (keeps existing vendor/lock; use a clean clone or delete them manually if Composer state is corrupted).
# $2 string '--no-npm' to not run npm.

echo "Installing PHP packages..."

# Include color values (must be done before `rm -rf vendor`).
PARENT_DIR=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )
. "$PARENT_DIR/colors.sh"

# Update/Install to ensure to have the latest version of the dependencies.
if [[ "$1" = "-u" ]] || [[ "$1" = "--update" ]]; then
	composer update
else
	# `composer.lock` is gitignored: drop vendor and lock so `composer install` resolves a fresh graph from
	rm -rf vendor composer.lock
	composer install
fi

if [[ '--no-npm' != $2 ]]; then
	echo "Running build..."
	# Minify js and css files.
	npm install && npm run build
fi

echo "${SUCCESS_C}Build done!${NO_C}"
