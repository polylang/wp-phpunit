<?php
/**
 * Bootstraps the integration tests
 * php version 5.6
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration
 */

namespace WP_Syntex\Polylang_Phpunit\Integration;

use WP_Syntex\Polylang_Phpunit\Bootstrap;

/**
 * Bootstraps the integration testing environment with WordPress, plugins, and other dependencies.
 *
 * @param  string       $rootDir    Path to the directory of the project.
 * @param  string       $testsDir   Path to the directory containing all tests.
 * @param  string       $phpVersion The PHP version required to run this test suite.
 * @param  array<mixed> $args       {
 *     Additional optional arguments.
 *
 *     @type array<bool|array<string|callable>> $plugins {
 *         A list of plugins to include and activate.
 *         Array keys are paths to the plugin's main file. The paths can be absolute, or relative to the plugins directory.
 *
 *         @type bool|array<string|callable> Values tell if the plugin must be included or not and can be:
 *           - bool: whether to include and activate the plugin or not.
 *           - array: {
 *                 @type string   $group A group or a list of groups, separated by commas. The plugin will be included and
 *                                       activated only if the tests run at least one of these groups. Optional.
 *                 @type callable $init  A callback to executed after all plugins are included. Optional.
 *             }
 *     }
 * }
 * @return void
 *
 * @phpstan-param array{
 *     plugins?: array<bool|array{
 *         group?: string,
 *         init?: callable
 *     }>
 * } $args
 */
function bootstrapSuite( $rootDir, $testsDir, $phpVersion, $args = [] ) {
	$args      = array_merge(
		[
			'plugins' => [],
		],
		$args
	);
	$bootstrap = new Bootstrap( 'Integration', $rootDir, $testsDir, $phpVersion );

	$bootstrap->initTestSuite();

	$rootDir      = rtrim( $rootDir, '/\\' );
	$wpPluginsDir = "{$rootDir}/tmp/plugins/";
	$wpThemesDir  = "{$rootDir}/tmp/themes";
	$allPlugins   = []; // Plugins that will be loaded. Format: {pluginPath} => {initCallback} (empty string if no callback is needed).
	$groups       = []; // Cache saying if groups are requested (with `--group=foo`). Format: {groupName} => {isRequested}.

	foreach ( $args['plugins'] as $path => $pluginArgs ) {
		if ( ! pathIsAbsolute( $path ) ) {
			// Make path absolute.
			$path = $wpPluginsDir . $path;
		}

		if ( ! file_exists( $path ) ) {
			trigger_error( 'One or several dependencies are not installed. Please run `composer install-plugins`.', E_USER_ERROR );
		}

		if ( is_bool( $pluginArgs ) ) {
			// No groups, no callbacks.
			if ( $pluginArgs ) {
				// Load this file.
				$allPlugins[ $path ] = '';
			}

			continue;
		}

		if ( ! is_array( $pluginArgs ) ) {
			// Only bool, array are allowed.
			continue;
		}

		if ( empty( $pluginArgs['init'] ) || ! is_callable( $pluginArgs['init'] ) ) {
			// Make sure the `init` key is set and has a valid value.
			$pluginArgs['init'] = '';
		}

		if ( isset( $pluginArgs['group'] ) ) {
			// The value of the `group` key must be an array of groups.
			if ( is_string( $pluginArgs['group'] ) ) {
				$pluginArgs['group'] = explode( ',', $pluginArgs['group'] );
			}

			if ( is_array( $pluginArgs['group'] ) ) {
				$pluginArgs['group'] = array_filter( array_map( 'trim', $pluginArgs['group'] ) );
			} else {
				$pluginArgs['group'] = [];
			}
		}

		if ( empty( $pluginArgs['group'] ) ) {
			// No groups required, load this file.
			$allPlugins[ $path ] = $pluginArgs['init'];
			continue;
		}

		foreach ( $pluginArgs['group'] as $group ) {
			if ( ! isset( $groups[ $group ] ) ) {
				// Put in cache.
				$groups[ $group ] = $bootstrap->isGroup( $group );
			}

			if ( $groups[ $group ] ) {
				// This group has been requested, load this file.
				$allPlugins[ $path ] = $pluginArgs['init'];
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
					call_user_func( $initCallback, $wpPluginsDir, $wpThemesDir );
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
