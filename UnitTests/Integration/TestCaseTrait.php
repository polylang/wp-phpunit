<?php
/**
 * Test Case for all of the integration tests.
 * php version 7.0
 *
 * @package WP_Syntex\Polylang_Phpunit\Integration
 */

namespace WP_Syntex\Polylang_Phpunit\Integration;

use InvalidArgumentException;
use PLL_Admin;
use PLL_Admin_Default_Term;
use PLL_Admin_Model;
use PLL_Install;
use WP_Syntex\Polylang_Phpunit\TestCaseTrait as GlobalTestCaseTrait;
use WP_UnitTest_Factory;

/**
 * Test Case for all of the integration tests.
 */
trait TestCaseTrait {

	use GlobalTestCaseTrait;

	/**
	 * Instance of PLL_Admin_Model.
	 *
	 * @var PLL_Admin_Model
	 */
	protected static $model;

	/**
	 * List of active plugins.
	 * Array of paths to plugin files, relative to the plugins directory.
	 *
	 * @var array<non-falsy-string>
	 */
	protected $activePlugins = [];

	/**
	 * Initialization before all tests run.
	 *
	 * @param  WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$options = PLL_Install::get_default_options();

		$options['hide_default']  = 0; // Force option to pre 2.1.5 value otherwise phpunit tests break on Travis.
		$options['media_support'] = 1; // Force option to pre 3.1 value otherwise phpunit tests break on Travis.

		self::$model = new PLL_Admin_Model( $options );
	}

	/**
	 * Deletes all languages after all tests have run.
	 *
	 * @return void
	 */
	public static function wpTearDownAfterClass() {
		self::deleteAllLanguages();
	}

	/**
	 * Prepares the test environment before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( ! empty( $this->activePlugins ) ) {
			add_filter( 'pre_option_active_plugins', [ $this, 'filterActivePlugins' ] );
		}
	}

	/**
	 * Cleans up the test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		parent::tear_down();

		if ( ! empty( $this->activePlugins ) ) {
			remove_filter( 'pre_option_active_plugins', [ $this, 'filterActivePlugins' ] );
		}

		self::$model->clean_languages_cache(); // We must do it before database ROLLBACK otherwhise it is impossible to delete the transient.

		$globals = [ 'current_screen', 'hook_suffix', 'wp_settings_errors', 'post_type', 'wp_scripts', 'wp_styles' ];

		foreach ( $globals as $global ) {
			$GLOBALS[ $global ] = null;
		}

		$_REQUEST = []; // WP Cleans up only $_POST and $_GET.
	}

	/**
	 * Filters the list of active plugins.
	 *
	 * @return array<string>
	 */
	public function filterActivePlugins() {
		return $this->activePlugins;
	}

	/**
	 * Helper function to create a language.
	 *
	 * @throws InvalidArgumentException If language is not created.
	 *
	 * @param  string       $locale Language locale.
	 * @param  array<mixed> $args   Allows to optionnally override the default values for the language.
	 * @return void
	 */
	protected static function createLanguage( $locale, $args = [] ) {
		$languages = include POLYLANG_DIR . '/settings/languages.php';
		$values    = $languages[ $locale ];

		$values['slug']       = $values['code'];
		$values['rtl']        = (int) ( 'rtl' === $values['dir'] );
		$values['term_group'] = 0; // Default term_group.

		$args             = array_merge( $values, $args );
		$linksModel       = self::$model->get_links_model();
		$pllAdmin         = new PLL_Admin( $linksModel );
		$adminDefaultTerm = new PLL_Admin_Default_Term( $pllAdmin );

		$errors = self::$model->add_language( $args );

		if ( is_wp_error( $errors ) ) {
			throw new InvalidArgumentException( $errors->get_error_message() );
		}

		$adminDefaultTerm->handle_default_term_on_create_language( $args );

		self::$model->clean_languages_cache();
	}

	/**
	 * Deletes all languages.
	 *
	 * @return void
	 */
	protected static function deleteAllLanguages() {
		$languages = self::$model->get_languages_list();

		if ( ! is_array( $languages ) ) {
			return;
		}

		// Delete the default categories first.
		$default_category = get_option( 'default_category' );
		$default_category = is_numeric( $default_category ) ? (int) $default_category : 0;

		if ( $default_category ) {
			$terms = wp_get_object_terms( $default_category, 'term_translations' );

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					wp_delete_term( $term->term_id, 'term_translations' ); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
				}
			}

			$terms = self::$model->term->get_translations( $default_category );

			foreach ( $terms as $termId ) {
				wp_delete_term( $termId, 'category' );
			}
		}

		foreach ( $languages as $lang ) {
			self::$model->delete_language( $lang->term_id ); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		}
	}
}
