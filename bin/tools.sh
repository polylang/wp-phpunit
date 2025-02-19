#!/usr/bin/env bash

PARENT_DIR=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

# Include color values.
. "$PARENT_DIR/colors.sh"

# Returns a distant content as a string.
#
# $1 string Contents URL.
getContent() {
	if [[ `which curl` ]]; then
		curl -s --proto '=https' "$1";
	elif [[ `which wget` ]]; then
		wget --https-only "$1" -q -O -
	fi
}

# Downloads a distant file.
#
# $1 string URL of the file to download.
# $2 string Destination path to download the file to.
download() {
	if [[ `which curl` ]]; then
		curl -sL --proto '=https' --max-redirs 1 "$1" > "$2";
	elif [[ `which wget` ]]; then
		wget --https-only -nv --max-redirect=1 -O "$2" "$1"
	fi
}

# Formats a message to be printed.
#
# $1     string The message.
# return string
formatMessage() {
	echo -e "  - $1"
}

# Clear Patchwork cache.
#
# Globals: PWD, ERROR_C, INFO_C, SUCCESS_C, NO_C
clear_patchwork_cache() {
	local DIR="${PWD}/tests/phpunit/patchwork-cache"
	local COUNT=0

	if [[ ! -d "${DIR}" ]]; then
		# No Patchwork cache in this project.
		return
	fi

	for file in "${DIR}"/*.php "${DIR}/index.csv"; do
		if [[ -f "${file}" ]]; then
			rm "${file}"
			((COUNT++))
		fi
	done

	if [ 0 == $COUNT ]; then
		formatMessage "${INFO_C}Patchwork cache${NO_C} folder is empty."
	else
		formatMessage "${SUCCESS_C}Patchwork cache${NO_C} cleared: ${COUNT} files deleted."
	fi
}