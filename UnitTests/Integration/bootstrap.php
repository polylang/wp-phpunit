<?php
/**
 * Bootstraps the PLL AI integration tests
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration
 */

namespace WP_Syntex\Polylang_Phpunit\Integration;

use WP_Syntex\Polylang_Phpunit\Bootstrap;
use WP_Syntex\Polylang_Phpunit\Integration\WooCommerce\Bootstrap as WooBootstrap;

/**
 * Bootstraps the integration testing environment with WordPress, PLL AI, and other dependencies.
 *
 * @param  array<bool|array<string|callable>> $plugins    {
 *     A list of plugins to include and activate.
 *     Array keys are paths to the plugin's main file. The paths can be absolute, or relative to the plugins directory.
 *
 *     @type bool|array<string|callable> Values tell if the plugin must be included or not and can be:
 *       - bool: whether to include and activate the plugin or not.
 *       - array: {
 *             @type string   $group A group or a list of groups, separated by commas. The plugin will be included and
 *                                   activated only if the tests run at least one of these groups. Optional.
 *             @type callable $init  A callback to executed after all plugins are included. Optional.
 *         }
 * }
 * @param  string                             $testsDir   Path to the directory containing all tests.
 * @param  string                             $phpVersion The PHP version required to run this test suite.
 * @return void
 */
function bootstrapSuite( $plugins, $testsDir, $phpVersion ) {
	$bootstrap = new Bootstrap( 'Integration', $testsDir, $phpVersion );

	$bootstrap->initTestSuite();

	$wpPluginsDir = dirname( $testsDir ) . '/tmp/plugins/';
	$wpThemesDir  = dirname( $testsDir ) . '/tmp/themes';
	$allPlugins   = []; // Plugins that will be loaded. Format: {pluginPath} => {initCallback} (empty string if no callback is needed).
	$groups       = []; // Cache saying if groups are requested (with `--group=foo`). Format: {groupName} => {isRequested}.

	foreach ( $plugins as $path => $args ) {
		if ( ! pathIsAbsolute( $path ) ) {
			// Make path absolute.
			$path = $wpPluginsDir . $path;
		}

		if ( ! file_exists( $path ) ) {
			trigger_error( 'One or several dependencies are not installed. Please run `composer install-plugins`.', E_USER_ERROR );
		}

		if ( is_bool( $args ) ) {
			// No groups, no callbacks.
			if ( $args ) {
				// Load this file.
				$allPlugins[ $path ] = '';
			}

			continue;
		}

		if ( ! is_array( $args ) ) {
			// Only bool, array are allowed.
			continue;
		}

		if ( empty( $args['init'] ) || ! is_callable( $args['init'] ) ) {
			// Make sure the `init` key is set and has a valid value.
			$args['init'] = '';
		}

		if ( isset( $args['group'] ) ) {
			// The value of the `group` key must be an array of groups.
			if ( is_string( $args['group'] ) ) {
				$args['group'] = explode( ',', $args['group'] );
			}

			if ( is_array( $args['group'] ) ) {
				$args['group'] = array_filter( array_map( 'trim', $args['group'] ) );
			} else {
				$args['group'] = [];
			}
		}

		if ( empty( $args['group'] ) ) {
			// No groups required, load this file.
			$allPlugins[ $path ] = $args['init'];
			continue;
		}

		foreach ( $args['group'] as $group ) { // @phpstan-ignore-line
			if ( ! isset( $groups[ $group ] ) ) {
				// Put in cache.
				$groups[ $group ] = $bootstrap->isGroup( $group );
			}

			if ( $groups[ $group ] ) {
				// This group has been requested, load this file.
				$allPlugins[ $path ] = $args['init'];
				break;
			}
		}
	} //end foreach

	tests_add_filter(
		'muplugins_loaded',
		function () use ( $allPlugins, $wpThemesDir ) {
			// Tell WP where to find the themes.
			register_theme_directory( $wpThemesDir );
			delete_site_transient( 'theme_roots' );

			// Require the plugins.
			foreach ( $allPlugins as $path => $initCallback ) {
				require_once $path;
			}
		}
	);

	tests_add_filter(
		'setup_theme',
		function () use ( $allPlugins, $wpPluginsDir, $wpThemesDir ) {
			// Init callbacks.
			foreach ( $allPlugins as $initCallback ) {
				if ( ! empty( $initCallback ) ) {
					call_user_func( $initCallback, $wpPluginsDir, $wpThemesDir ); // @phpstan-ignore-line
				}
			}
		}
	);

	// Start up the WP testing environment.
	$bootstrap->bootstrapWpSuite();
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
