<?php
/**
 * Bootstraps the PLL AI unit tests
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration
 */

namespace WP_Syntex\Polylang_Phpunit\Unit;

use WP_Syntex\Polylang_Phpunit\Bootstrap;

/**
 * Bootstraps the unit testing environment.
 *
 * @param  string $testsDir   Path to the directory containing all tests.
 * @param  string $phpVersion The PHP version required to run this test suite.
 * @return void
 */
function bootstrapSuite( $testsDir, $phpVersion ) {
	( new Bootstrap( 'unit', $testsDir, $phpVersion ) )->initTestSuite();
}
