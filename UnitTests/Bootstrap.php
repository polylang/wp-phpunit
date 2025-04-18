<?php
/**
 * Class to use to bootstrap tests.
 * php version 7.0
 *
 * @package WP_Syntex\Polylang_Phpunit
 */

namespace WP_Syntex\Polylang_Phpunit;

use CliArgs\CliArgs;

/**
 * Class to use to bootstrap tests.
 */
class Bootstrap {

	/**
	 * The test suite: 'Unit' or 'Integration'.
	 *
	 * @var string
	 */
	private $suite;

	/**
	 * Path to the directory of the project.
	 *
	 * @var string
	 */
	private $rootDir;

	/**
	 * Path to the directory containing all tests.
	 *
	 * @var string
	 */
	private $testsDir;

	/**
	 * The PHP version required to run this test suite.
	 *
	 * @var string
	 */
	private $phpVersion;

	/**
	 * Addition config for CliArgs.
	 *
	 * @see https://github.com/cheprasov/php-cli-args#config
	 *
	 * @var array<array<mixed>|string>
	 */
	private $cliArgs;

	/**
	 * Instance of CliArgs.
	 *
	 * @var CliArgs|null
	 */
	private $cliArgsInst;

	/**
	 * Cache for some paths.
	 *
	 * @var array<string>
	 */
	private $paths = [];

	/**
	 * Constructor.
	 *
	 * @param  string                     $testSuite  Directory name of the test suite. Possible values are
	 *                                                'Integration' and 'Unit'. Default is 'Unit'.
	 * @param  string                     $rootDir    Path to the directory of the project.
	 * @param  string                     $testsDir   Path to the directory containing the tests, fixtures, etc.
	 * @param  string                     $phpVersion The PHP version required to run this test suite.
	 * @param  array<array<mixed>|string> $cliArgs    Addition config for CliArgs.
	 * @return void
	 */
	public function __construct( $testSuite, $rootDir, $testsDir, $phpVersion, array $cliArgs = [] ) {
		$this->suite      = 'Integration' === $testSuite ? $testSuite : 'Unit';
		$this->rootDir    = rtrim( $rootDir, '/\\' );
		$this->testsDir   = rtrim( $testsDir, '/\\' );
		$this->phpVersion = $phpVersion;
		$this->cliArgs    = $cliArgs;
	}

	/**
	 * Initialize the test suite.
	 *
	 * @return void
	 */
	public function initTestSuite() {
		$this->checkReadiness();
		$this->initConstants();

		// Ensure server variable is set for WP email functions.
		if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
			$_SERVER['SERVER_NAME'] = 'localhost';
		}

		/**
		 * Load Patchwork before everything else in order to allow us to redefine WordPress, 3rd party, and plugin's
		 * functions.
		 */
		$patchworkPath = dirname( __DIR__ ) . '/vendor/antecedent/patchwork/Patchwork.php';

		if ( file_exists( $patchworkPath ) ) {
			require_once $patchworkPath;
		}

		if ( 'Integration' === $this->suite ) {
			/**
			 * Integration tests:
			 * Give access to tests_add_filter() function.
			 */
			require_once $this->getWpTestsDir() . '/includes/functions.php';
		} else {
			/**
			 * Unit tests:
			 * Give access to WP's compatibility functions like `str_ends_with()`.
			 */
			require_once  ABSPATH . 'wp-includes/compat.php';
		}
	}

	/**
	 * Returns the CliArgs instance.
	 *
	 * @return CliArgs
	 */
	public function getCliArgsInst() {
		if ( isset( $this->cliArgsInst ) ) {
			return $this->cliArgsInst;
		}

		$this->cliArgsInst = new CliArgs(
			array_merge(
				[
					'group' => [
						'default' => [],
						'filter'  => function ( $value, $default ) {
							if ( empty( $value ) ) {
								return $default;
							}

							if ( is_string( $value ) ) {
								$value = explode( ',', $value );
								$value = array_map( 'trim', $value );
								$value = array_filter( $value );
							}

							if ( ! is_array( $value ) ) {
								return $default;
							}

							return $value;
						},
					],
				],
				$this->cliArgs
			)
		);

		return $this->cliArgsInst;
	}

	/**
	 * Tells if the suite is running for a given group.
	 *
	 * @param  string $group A group name.
	 * @return bool
	 */
	public function isGroup( $group ) {
		$groups = (array) $this->getCliArgsInst()->getArg( 'group' );
		return in_array( $group, $groups, true );
	}

	/**
	 * Starts up the WordPress testing environment.
	 *
	 * @return void
	 */
	public function bootstrapWpSuite() {
		require_once $this->getWpTestsDir() . '/includes/bootstrap.php';
	}

	/**
	 * Returns the directory path to the WordPress testing environment (without trailing slash).
	 *
	 * @return string
	 */
	public function getWpTestsDir() {
		if ( isset( $this->paths['wp_tests_dir'] ) ) {
			return $this->paths['wp_tests_dir'];
		}

		// In wp-env Docker container.
		$this->paths['wp_tests_dir'] = getenv( 'WP_TESTS_DIR' );

		if ( file_exists( $this->paths['wp_tests_dir'] . '/includes/' ) ) {
			return $this->paths['wp_tests_dir'];
		}

		// In local `tmp` dir.
		$this->paths['wp_tests_dir'] = WPSYNTEX_PROJECT_PATH . 'tmp/wordpress-tests-lib';

		if ( file_exists( $this->paths['wp_tests_dir'] . '/includes/' ) ) {
			return $this->paths['wp_tests_dir'];
		}

		// In dependency's `tmp` dir.
		$this->paths['wp_tests_dir'] = WPSYNTEX_PROJECT_PATH . 'vendor/wpsyntex/wp-phpunit/tmp/wordpress-tests-lib';

		if ( file_exists( $this->paths['wp_tests_dir'] . '/includes/' ) ) {
			return $this->paths['wp_tests_dir'];
		}

		// Travis CI & Vagrant SSH tests directory.
		$this->paths['wp_tests_dir'] = '/tmp/wordpress-tests-lib';

		if ( file_exists( $this->paths['wp_tests_dir'] . '/includes/' ) ) {
			return $this->paths['wp_tests_dir'];
		}

		// If the tests' includes directory does not exist, try a relative path to Core tests directory.
		$this->paths['wp_tests_dir'] = '../../../../tests/phpunit';

		// Check it again. If it doesn't exist, stop here and post a message as to why we stopped.
		if ( ! file_exists( $this->paths['wp_tests_dir'] . '/includes/' ) ) {
			trigger_error( 'Unable to run the integration tests, because the WordPress test suite could not be located.', E_USER_ERROR );
		}

		return $this->paths['wp_tests_dir'];
	}

	/**
	 * Returns the directory path to the WordPress root dir (without trailing slash).
	 *
	 * @return string
	 */
	public function getWpDir() {
		if ( isset( $this->paths['wp_dir'] ) ) {
			return $this->paths['wp_dir'];
		}

		$this->paths['wp_dir'] = dirname( $this->getWpTestsDir() ) . '/wordpress';

		return $this->paths['wp_dir'];
	}

	/**
	 * Check the system's readiness to run the tests.
	 *
	 * @return void
	 */
	private function checkReadiness() {
		$phpversion = phpversion();

		if ( ! $phpversion || version_compare( $phpversion, $this->phpVersion ) < 0 ) {
			trigger_error(
				sprintf(
					'Unit Tests for this project require PHP %s or higher.',
					$this->phpVersion // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				),
				E_USER_ERROR
			);
		}
	}

	/**
	 * Initialize the constants.
	 *
	 * @return void
	 */
	private function initConstants() {
		define( 'WPSYNTEX_PROJECT_PATH', $this->rootDir . DIRECTORY_SEPARATOR );
		define( 'WPSYNTEX_TESTS_PATH', $this->testsDir . DIRECTORY_SEPARATOR . $this->suite . DIRECTORY_SEPARATOR );
		define( 'WPSYNTEX_FIXTURES_PATH', $this->testsDir . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR );
		define( 'WPSYNTEX_TESTSUITE_PATH', $this->getWpTestsDir() . DIRECTORY_SEPARATOR );
		define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', WPSYNTEX_PROJECT_PATH . 'vendor/yoast/phpunit-polyfills/' );

		if ( 'Unit' === $this->suite ) {
			if ( ! defined( 'ABSPATH' ) ) {
				define( 'ABSPATH', $this->getWpDir() . DIRECTORY_SEPARATOR );
			}

			if ( ! defined( 'WPINC' ) ) {
				define( 'WPINC', 'wp-includes' );
			}
		}

		if ( ! defined( 'WPSYNTEX_IS_TESTING' ) ) {
			define( 'WPSYNTEX_IS_TESTING', true );
		}
	}
}
