<?php
/**
 * Test Case for all of the unit tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Unit
 */

namespace WP_Syntex\Polylang_Phpunit\Unit;

use WP_Syntex\Polylang_Phpunit\TestCaseTrait;
use Yoast\WPTestUtils\BrainMonkey\YoastTestCase as BaseTestCase;

/**
 * Test Case for all of the unit tests.
 */
abstract class AbstractTestCase extends BaseTestCase {
	use TestCaseTrait;

	/**
	 * Set to true in root TestCase to stub the WP native translation functions in the set_up().
	 *
	 * @var bool
	 */
	protected static $stubTranslationFunctions = false;

	/**
	 * Set to true in root TestCase to stub the WP native escaping functions in the set_up().
	 *
	 * @var bool
	 */
	protected static $stubEscapeFunctions = false;

	/**
	 * Prepares the test environment before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( static::$stubTranslationFunctions ) {
			$this->stubTranslationFunctions();
		}

		if ( static::$stubEscapeFunctions ) {
			$this->stubEscapeFunctions();
		}
	}

	/**
	 * Cleans up the test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $GLOBALS['wpdb'] );

		parent::tear_down();
	}
}
