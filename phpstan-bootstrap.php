<?php
/**
 * Bootstraps PHPStan
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit
 */

define( 'WPSYNTEX_PROJECT_PATH', __DIR__ . DIRECTORY_SEPARATOR );
define( 'WPSYNTEX_TESTS_PATH', WPSYNTEX_PROJECT_PATH . 'UnitTests' . DIRECTORY_SEPARATOR . 'Integration' . DIRECTORY_SEPARATOR );
define( 'WPSYNTEX_FIXTURES_PATH', WPSYNTEX_PROJECT_PATH . 'UnitTests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR );
define( 'WPSYNTEX_TESTSUITE_PATH', WPSYNTEX_PROJECT_PATH . 'tmp/wordpress-tests-lib' . DIRECTORY_SEPARATOR );
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', WPSYNTEX_PROJECT_PATH . 'vendor/yoast/phpunit-polyfills/' );
define( 'ABSPATH', WPSYNTEX_PROJECT_PATH . 'tmp/wordpress-tests-lib' . DIRECTORY_SEPARATOR );
define( 'WPSYNTEX_IS_TESTING', true );
