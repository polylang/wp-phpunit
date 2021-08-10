<?php
/**
 * Do some replacements in the test suite's files.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Tests
 */

namespace WP_Syntex\Polylang_Phpunit\Tests;

use Exception;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

if ( ! empty( $_SERVER['PWD'] ) && is_string( $_SERVER['PWD'] ) && file_exists( $_SERVER['PWD'] . '/vendor/autoload.php' ) ) {
	require $_SERVER['PWD'] . '/vendor/autoload.php';
} elseif ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require __DIR__ . '/../vendor/autoload.php';
}

/**
 * Do some replacements in the test suite's files.
 *
 * @since  1.0
 * @return void
 */
function fix_tests_suite() {
	$io        = new SymfonyStyle( new ArgvInput(), new ConsoleOutput() );
	$path_root = __DIR__ . '/../tmp/wordpress-tests-lib/';
	$errors    = 0;
	$rel_paths = [
		'includes/testcase.php' => [
			'@function assertEqualsWithDelta\([^)]+\) {@msU' => 'function assertEqualsWithDelta( $expected, $actual, float $delta, string $message = \'\'): void {', // `assertEqualsWithDelta()` should match its parent.
		],
	];

	$io->title( 'Replacements in tests suite' );

	foreach ( $rel_paths as $rel_path => $to_replace ) {
		$full_path    = $path_root . $rel_path;
		$not_replaced = [];

		if ( ! file_exists( $full_path ) ) {
			$io->error( "Failed to locate file $rel_path." );
			++$errors;
			continue;
		}

		try {
			$contents = file_get_contents( $full_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

			if ( ! is_string( $contents ) ) {
				$io->error( "Failed to open file $rel_path." );
				++$errors;
				continue;
			}
		} catch ( Exception $e ) {
			$io->error( "Failed to open file $rel_path." );
			++$errors;
			continue;
		}

		foreach ( $to_replace as $pattern => $replacement ) {
			$new_contents = preg_replace( $pattern, $replacement, $contents, 1 );

			if ( $new_contents !== $contents && is_string( $new_contents ) ) {
				$contents = $new_contents;
			} else {
				$not_replaced[] = $replacement;
			}
		}

		if ( ! empty( $not_replaced ) ) {
			$message      = count( $not_replaced ) === 1 ? 'Replacement not done in file %s: `%s`.' : 'Replacements not done in file %s: `%s`.';
			$not_replaced = implode( '`, `', $not_replaced );
			$io->error( sprintf( $message, $rel_path, $not_replaced ) );
			++$errors;
			continue;
		}

		$result = file_put_contents( $full_path, $contents ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents

		if ( false === $result ) {
			$io->error( "Failed to perform replacements in file $rel_path." );
			++$errors;
		}
	} // endforeah.

	if ( ! $errors ) {
		$io->success( 'All replacements done in tests suite.' );
		return;
	}

	$count = array_sum( array_map( 'count', $rel_paths ) );

	if ( $count > $errors ) {
		$io->success( 'Other replacements done in tests suite.' );
	}
}

fix_tests_suite();
