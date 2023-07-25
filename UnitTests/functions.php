<?php
/**
 * Various functions.
 * php version 7.0
 *
 * @package WP_Syntex\Polylang_Phpunit
 */

namespace WP_Syntex\Polylang_Phpunit;

/**
 * Returns colors used in CLI.
 *
 * @see bin/colors.sh
 *
 * @return string[]
 *
 * @phpstan-return array{
 *     info: non-falsy-string,
 *     success: non-falsy-string,
 *     error: non-falsy-string,
 *     no_color: non-falsy-string
 * }
 */
function getCliColors(): array {
	return [
		'info'     => "\033[0;36m",
		'success'  => "\033[0;32m",
		'error'    => "\033[0;31m",
		'no_color' => "\033[0m",
	];
}
