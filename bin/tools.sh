#!/usr/bin/env bash

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