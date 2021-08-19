<?php
/**
 * Modified version of WooCommerce test bootstrap.
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration\WooCommerce
 */

namespace WP_Syntex\Polylang_Phpunit\Integration\WooCommerce;

use Automattic\WooCommerce\Admin\Install as WooInstall;
use WC_Install;

class Bootstrap {

	/**
	 * Init WooCommerce (database, etc).
	 *
	 * @param  string $pluginsDir Path to the WP plugins dir.
	 * @return void
	 */
	public static function initWooCommerce( $pluginsDir ) {
		if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			return;
		}

		// Clean existing install first.
		define( 'WP_UNINSTALL_PLUGIN', true );
		define( 'WC_REMOVE_ALL_DATA', true );

		include $pluginsDir . 'woocommerce/uninstall.php';

		WC_Install::install();

		// Initialize the WC Admin package.
		WooInstall::create_tables();
		WooInstall::create_events();

		$GLOBALS['wp_roles'] = null;
		wp_roles();

		echo esc_html( 'Installing WooCommerce...' . PHP_EOL );
	}

	/**
	 * Load WC-specific test cases and factories.
	 *
	 * @param  string $pluginsDir Path to the WP plugins dir.
	 * @return void
	 */
	public static function includeWooCommerceSuite( $pluginsDir ) {
		add_theme_support( 'woocommerce' );

		$testsDir = $pluginsDir . 'woocommerce/tests' . version_compare( WC()->version, '4.2', '<' ) ? '' : '/legacy';

		// woocommerce/framework.
		require_once $testsDir . '/framework/class-wc-unit-test-factory.php';
		require_once $testsDir . '/framework/class-wc-mock-session-handler.php';
		require_once $testsDir . '/framework/class-wc-mock-wc-data.php';
		require_once $testsDir . '/framework/class-wc-payment-token-stub.php';

		// Test cases.
		require_once $testsDir . '/includes/wp-http-testcase.php'; // WC 3.5+.
		require_once $testsDir . '/framework/class-wc-unit-test-case.php';
		require_once $testsDir . '/framework/class-wc-api-unit-test-case.php';

		// Helpers.
		require_once $testsDir . '/framework/helpers/class-wc-helper-product.php';
		require_once $testsDir . '/framework/helpers/class-wc-helper-coupon.php';
		require_once $testsDir . '/framework/helpers/class-wc-helper-fee.php';
		require_once $testsDir . '/framework/helpers/class-wc-helper-shipping.php';
		require_once $testsDir . '/framework/helpers/class-wc-helper-customer.php';
		require_once $testsDir . '/framework/helpers/class-wc-helper-order.php';
		require_once $testsDir . '/framework/helpers/class-wc-helper-shipping-zones.php';
		require_once $testsDir . '/framework/helpers/class-wc-helper-payment-token.php';
	}
}
