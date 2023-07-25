#!/usr/bin/env bash

# Colors used to print messages to stdout.
# See `UnitTests/functions.php`.
if [[ "$(type -t tput)" == 'file' ]] && [[ $(tput colors) ]]; then
	INFO_C="$(tput setaf 6)"
	SUCCESS_C="$(tput setaf 2)"
	ERROR_C="$(tput setaf 1)"
	NO_C="$(tput sgr0)"
else
	INFO_C='\033[0;36m'
	SUCCESS_C='\033[0;32m'
	ERROR_C='\033[0;31m'
	NO_C='\033[0m'
fi
