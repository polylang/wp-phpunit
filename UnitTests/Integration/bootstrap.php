<?php
/**
 * Bootstraps the PLL AI integration tests
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration
 */

namespace WP_Syntex\Polylang_Phpunit\Integration;

use Automattic\WooCommerce\Admin\Install as WooInstall;
use WC_Install;
use WP_Syntex\Polylang_Phpunit\Bootstrap;

/**
 * Bootstraps the integration testing environment with WordPress, PLL AI, and other dependencies.
 *
 * @param  array<bool|string|array<string>> $plugins    {
 *     A list of plugins to include and activate.
 *     Array keys are paths to the plugin's main file. The paths can be absolute, or relative to the plugins directory.
 *
 *     @type bool|string|array<string> Values tell if the plugin must be included or not and can be:
 *       - bool: whether to include and activate the plugin or not.
 *       - string: the name of a test group. The plugin will be included and activated only if the tests run this group.
 *         Can also be a list of groups, separated by commas: the plugin will be included and activated if the tests run
 *         at least one of these groups.
 *       - array: a list of test groups (see string format).
 * }
 * @param  string                           $testsDir   Path to the directory containing all tests.
 * @param  string                           $phpVersion The PHP version required to run this test suite.
 * @return void
 */
function bootstrapSuite( $plugins, $testsDir, $phpVersion ) {
	$pllaiTestBootstrap = new Bootstrap( 'Integration', $testsDir, $phpVersion );

	$pllaiTestBootstrap->initTestSuite();

	$wpPluginsDir = dirname( $testsDir ) . '/tmp/plugins/';
	$allPlugins   = [];
	$groups       = [];

	foreach ( $plugins as $path => $load ) {
		if ( ! pathIsAbsolute( $path ) ) {
			$path = $wpPluginsDir . $path;
		}

		if ( ! file_exists( $path ) ) {
			trigger_error( 'One or several dependencies are not installed. Please run `composer install-plugins`.', E_USER_ERROR );
		}

		if ( is_bool( $load ) ) {
			$allPlugins[ $path ] = $load;
			continue;
		}

		if ( is_string( $load ) ) {
			$load = explode( ',', $load );
		}

		if ( ! is_array( $load ) ) {
			continue;
		}

		$load = array_map( 'trim', $load );

		foreach ( array_filter( $load ) as $group ) {
			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = $pllaiTestBootstrap->isGroup( $group );
			}

			if ( $groups[ $group ] ) {
				$allPlugins[ $path ] = true;
				break;
			}
		}

		$allPlugins[ $path ] = false;
	} //end foreach

	tests_add_filter(
		'muplugins_loaded',
		function () use ( $allPlugins, $testsDir ) {
			// Tell WP where to find the themes.
			register_theme_directory( dirname( $testsDir ) . '/tmp/themes' );
			delete_site_transient( 'theme_roots' );

			// Require the plugins.
			foreach ( $allPlugins as $path => $load ) {
				if ( $load ) {
					require_once $path;
				}
			}
		}
	);

	tests_add_filter(
		'setup_theme',
		function () use ( $wpPluginsDir, $plugins, $groups ) {
			// Trigger plugins' activation hooks.
			foreach ( $plugins as $path => $load ) {
				if ( $load ) {
					do_action( 'activate_' . plugin_basename( $path ) );
				}
			}

			// some custom inits.
			if ( ! empty( $groups['withWoo'] ) ) {
				initWoocommerce( $wpPluginsDir );
			}
		}
	);

	// Start up the WP testing environment.
	$pllaiTestBootstrap->bootstrapWpSuite();
}

/**
 * Test if a given path is absolute.
 * For example, '/foo/bar', or 'c:\windows'.
 *
 * @param  string $path File path.
 * @return bool         True if path is absolute, false is not absolute.
 */
function pathIsAbsolute( $path ) {
	/*
	 * This is definitive if true but fails if $path does not exist or contains
	 * a symbolic link.
	 */
	if ( realpath( $path ) === $path ) {
		return true;
	}

	if ( strlen( $path ) === 0 || '.' === $path[0] ) {
		return false;
	}

	// Windows allows absolute paths like this.
	if ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) ) {
		return true;
	}

	// A path starting with / or \ is absolute; anything else is relative.
	return ( '/' === $path[0] || '\\' === $path[0] );
}

/**
 * Init WooCommerce (database, etc).
 *
 * @param  string $pluginsDir Path to the WP plugins dir.
 * @return void
 */
function initWoocommerce( $pluginsDir ) {
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
