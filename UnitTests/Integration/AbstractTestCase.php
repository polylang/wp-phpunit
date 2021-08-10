<?php
/**
 * Test Case for all of the integration tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration
 */

namespace WP_Syntex\Polylang_Phpunit\Integration;

use WP_Syntex\Polylang_Phpunit\TestCaseTrait;
use WP_UnitTestCase;

/**
 * Test Case for all of the integration tests.
 */
abstract class AbstractTestCase extends WP_UnitTestCase {
	use TestCaseTrait;

	/**
	 * List of active plugins.
	 *
	 * @var array<string>
	 */
	protected $activePlugins = [];

	/**
	 * Prepares the test environment before each test.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		if ( ! empty( $this->activePlugins ) ) {
			add_filter( 'pre_option_active_plugins', [ $this, 'filterActivePlugins' ] );
		}
	}

	/**
	 * Cleans up the test environment after each test.
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		if ( ! empty( $this->activePlugins ) ) {
			remove_filter( 'pre_option_active_plugins', [ $this, 'filterActivePlugins' ] );
		}
	}

	/**
	 * Filters the list of active plugins.
	 *
	 * @return array<string>
	 */
	public function filterActivePlugins() {
		return $this->activePlugins;
	}
}
