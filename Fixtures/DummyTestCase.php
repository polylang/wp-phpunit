<?php
/**
 * Dummy test case so PHPStan can analyze TestCaseTrait.
 * php version 7.0
 *
 * @package WP_Syntex\Polylang_Phpunit\Fixtures
 */

namespace WP_Syntex\Polylang_Phpunit\Fixtures;

use WP_Syntex\Polylang_Phpunit\Integration\TestCaseTrait;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Dummy test case so PHPStan can analyze TestCaseTrait.
 */
class DummyTestCase extends TestCase {

	use TestCaseTrait;

	/**
	 * Sets up the fixture, for example, open a network connection.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		// Must be set before calling `parent::set_up()`.
		$this->activePlugins = [
			'wp-all-import-pro/wp-all-import-pro.php',
		];

		parent::setUp();
	}
}
