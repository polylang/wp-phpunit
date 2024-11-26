#!/usr/bin/env bash

# Returns a distant content as a string.
#
# $1 string Contents URL.
getContent() {
	if [[ `which curl` ]]; then
		curl -s "$1";
	elif [[ `which wget` ]]; then
		wget "$1" -q -O -
	fi
}

# Downloads a distant file.
#
# $1 string URL of the file to download.
# $2 string Destination path to download the file to.
download() {
	if [[ `which curl` ]]; then
		curl -sL --proto '=https' --max-redirs 2 "$1" > "$2";
	elif [[ `which wget` ]]; then
		wget --https-only -nv --max-redirect=2 -O "$2" "$1"
	fi
}