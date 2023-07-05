<?php
/**
 * Dummy test case so PHPStan can analyze TestCaseTrait.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Fixtures
 */

namespace WP_Syntex\Polylang_Phpunit\Fixtures;

use WP_Syntex\Polylang_Phpunit\Integration\TestCaseTrait;
use WP_UnitTestCase;

/**
 * Dummy test case so PHPStan can analyze TestCaseTrait.
 */
class DummyTestCase extends WP_UnitTestCase {

	/**
	 * List of active plugins.
	 *
	 * @var array<string>
	 */
	protected $activePlugins = [
		'wp-all-import-pro/wp-all-import-pro.php',
	];

	use TestCaseTrait;
}
