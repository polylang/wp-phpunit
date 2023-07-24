<?php
/**
 * Modified version of WooCommerce test bootstrap.
 * php version 7.0
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration\WooCommerce
 */

namespace WP_Syntex\Polylang_Phpunit\Integration\WooCommerce;

use WC_Install;

/**
 * Bootstrap class when using WooComerce.
 */
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

		if ( class_exists( '\Automattic\WooCommerce\Internal\Admin\Install' ) ) {
			// WC 6.4.0 to 6.4.1.
			\Automattic\WooCommerce\Internal\Admin\Install::create_tables();
			\Automattic\WooCommerce\Internal\Admin\Install::create_events();
		} elseif ( class_exists( '\Automattic\WooCommerce\Admin\Install' ) ) {
			// WC 4.0.0 to 6.3.1.
			\Automattic\WooCommerce\Admin\Install::create_tables();
			\Automattic\WooCommerce\Admin\Install::create_events();
		}

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

		$testsDir = $pluginsDir . 'woocommerce/tests/legacy';

		// woocommerce/framework.
		require_once $testsDir . '/framework/class-wc-unit-test-factory.php';
		require_once $testsDir . '/framework/class-wc-mock-session-handler.php';
		require_once $testsDir . '/framework/class-wc-mock-wc-data.php';
		require_once $testsDir . '/framework/class-wc-payment-token-stub.php';

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
