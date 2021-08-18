<?php
/**
 * Test Case for all of the unit tests.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Unit
 */

namespace WP_Syntex\Polylang_Phpunit\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use WP_Syntex\Polylang_Phpunit\TestCaseTrait;

/**
 * Test Case for all of the unit tests.
 */
abstract class AbstractTestCase extends PHPUnitTestCase {
	use MockeryPHPUnitIntegration;
	use TestCaseTrait;

	/**
	 * Set to true in root TestCase to mock the common WP Functions in the setUp().
	 *
	 * @var bool
	 */
	protected static $mockCommonWpFunctionsInSetUp = false;

	/**
	 * Prepares the test environment before each test.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		Monkey\setUp();

		if ( static::$mockCommonWpFunctionsInSetUp ) {
			$this->mockCommonWpFunctions();
		}
	}

	/**
	 * Cleans up the test environment after each test.
	 *
	 * @return void
	 */
	public function tearDown() {
		unset( $GLOBALS['wpdb'] );

		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Mock common WP functions.
	 *
	 * @return void
	 */
	protected function mockCommonWpFunctions() {
		Functions\stubs(
			[
				'__'           => null,
				'esc_attr__'   => null,
				'esc_html__'   => null,
				'_x'           => null,
				'esc_attr_x'   => null,
				'esc_html_x'   => null,
				'_n'           => static function( $single, $plural, $number ) {
					return 1 === $number ? $single: $plural;
				},
				'_nx'          => static function( $single, $plural, $number ) {
					return 1 === $number ? $single: $plural;
				},
				'esc_attr'     => null,
				'esc_html'     => null,
				'esc_textarea' => null,
				'esc_url'      => null,
			]
		);

		$functions = [
			'_e',
			'esc_attr_e',
			'esc_html_e',
			'_ex',
		];

		foreach ( $functions as $function ) {
			Functions\when( $function )->echoArg();
		}
	}
}
