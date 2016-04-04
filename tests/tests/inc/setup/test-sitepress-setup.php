<?php

class Test_SitePress_Setup extends WPML_UnitTestCase {

	function setUp() {
		wpml_get_setup_instance()->set_active_languages( array( 'en', 'fr' ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2529
	 */
	public function test_insert_default_category() {
		global $sitepress;

		$active_langs = array_keys( $sitepress->get_active_languages() );
		// Get default categories.
		$default_categories = $sitepress->get_setting( 'default_categories', array() );
		foreach ( array( 'en', 'fr', 'test_locale' ) as $lang_code ) {
			SitePress_Setup::insert_default_category( $lang_code );
			$updated_categories = $sitepress->get_setting( 'default_categories', array() );
			foreach ( $active_langs as $code ) {
				$this->assertEquals( $default_categories[ $code ], $updated_categories[ $code ] );
			}
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2529
	 */
	public function test_insert_default_category_with_scenario() {
		global $sitepress;
		$active_langs = array_keys( $sitepress->get_active_languages() );
		// Get default categories.
		$default_categories = $sitepress->get_setting( 'default_categories', array() );
		wp_cache_init();
		foreach ( $active_langs as $active_lang ) {
			wp_update_term( $default_categories[ $active_lang ], 'category', array(
				'name' => $active_lang . ' Uncategorized',
				'slug' => $active_lang . '-uncategorized',
			));
			$term_data = get_term( $default_categories[ $active_lang ], 'category', ARRAY_A );
			$this->assertEquals( $active_lang . ' Uncategorized', $term_data['name'] );
			$this->assertEquals( $active_lang . '-uncategorized', $term_data['slug'] );
			$lang_details = $sitepress->get_element_language_details( $default_categories[ $active_lang ], 'tax_category' );
			if ( 'fr' === $active_lang ) {
				$this->assertEquals( 'en', $lang_details->source_language_code );
				$this->assertEquals( 'fr', $lang_details->language_code );
			} else {
				$this->assertEquals( null, $lang_details->source_language_code );
				$this->assertEquals( 'en', $lang_details->language_code );
			}
		}

		wpml_get_setup_instance()->set_active_languages( array( 'en', 'fr', 'de' ) );
		$active_langs = array_keys( $sitepress->get_active_languages() );
		$default_categories = $sitepress->get_setting( 'default_categories', array() );
		foreach ( $active_langs as $active_lang ) {
			$term_data = get_term( $default_categories[ $active_lang ], 'category', ARRAY_A );
			if ( 'de' !== $active_lang ) {
				$this->assertEquals( $active_lang . ' Uncategorized', $term_data['name'] );
				$this->assertEquals( $active_lang . '-uncategorized', $term_data['slug'] );
			} else {
				$lang_details = $sitepress->get_element_language_details( $default_categories[ $active_lang ], 'tax_category' );
				$this->assertEquals( 'en', $lang_details->source_language_code );
				$this->assertEquals( 'de', $lang_details->language_code );
			}
		}
	}
}
