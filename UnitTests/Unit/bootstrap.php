<?php
/**
 * Bootstraps the unit tests
 * php version 7.0
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration
 */

namespace WP_Syntex\Polylang_Phpunit\Unit;

use WP_Syntex\Polylang_Phpunit\Bootstrap;

/**
 * Bootstraps the unit testing environment.
 *
 * @param  string $rootDir    Path to the directory of the project.
 * @param  string $testsDir   Path to the directory containing all tests.
 * @param  string $phpVersion The PHP version required to run this test suite.
 * @return void
 */
function bootstrapSuite( $rootDir, $testsDir, $phpVersion ) {
	( new Bootstrap( 'unit', $rootDir, $testsDir, $phpVersion ) )->initTestSuite();
}
