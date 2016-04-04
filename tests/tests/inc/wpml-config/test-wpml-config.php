<?php

class Test_WPML_Config extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1714
	 *
	 */
	function test_load_config_pre_process() {
		global $iclTranslationManagement;

		$iclTranslationManagement->settings['__custom_types_readonly_config_prev']  = null;
		$iclTranslationManagement->settings['__custom_fields_readonly_config_prev'] = null;

		WPML_Config::load_config_pre_process();

		$this->assertTrue( is_array( $iclTranslationManagement->settings['__custom_types_readonly_config_prev'] ) );
		$this->assertTrue( is_array( $iclTranslationManagement->settings['__custom_fields_readonly_config_prev'] ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-339
	 */
	function test_parse_wpml_config_files() {
		global $iclTranslationManagement, $sitepress, $wpdb;

		if ( ! empty( $wpdb->termmeta ) ) {
			WPML_Config::$wpml_config_files[] = WPML_TEST_DIR . '/res/wpml-term-meta-config.xml';
		}
		WPML_Config::$wpml_config_files[] = WPML_TEST_DIR . '/res/wpseo-wpml-config.xml';
		WPML_Config::$wpml_config_files[] = WPML_TEST_DIR . '/res/wcml-wpml-config.xml';
		WPML_Config::parse_wpml_config_files();
		WPML_Config::load_config_post_process();

		$this->assertTrue( has_filter( 'get_translatable_taxonomies' ) );
		$this->assertTrue( has_filter( 'get_translatable_documents' ) );

		wpml_test_reg_custom_post_type( 'product' );
		wpml_test_reg_custom_taxonomy( 'product_cat' );

		$this->assertTrue( (bool) $sitepress->is_translated_post_type( 'product' ) );
		$this->assertArrayHasKey( 'product', apply_filters( 'get_translatable_documents', array() ) );
		$this->assertTrue( in_array( 'product_cat', $sitepress->get_translatable_taxonomies() ) );

		$this->assertTrue( isset( $iclTranslationManagement->settings['custom_fields_translation']['_yoast_wpseo_title'] ) );

		_unregister_post_type( 'product' );
		_unregister_taxonomy( 'product_cat' );

		if ( ! empty( $wpdb->termmeta ) ) {
			$this->assertTrue( isset( $iclTranslationManagement->settings['custom_term_fields_translation'] ) );
			$settings_factory = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );
			$this->assertEquals( WPML_TRANSLATE_CUSTOM_FIELD, $settings_factory->term_meta_setting( '_test_term_meta_key_translate' )->status() );
			$this->assertEquals( WPML_COPY_CUSTOM_FIELD, $settings_factory->term_meta_setting( '_test_term_meta_key_copy' )->status() );
			$ignored_setting = $settings_factory->term_meta_setting( '_test_term_meta_key_ignore' );
			$this->assertEquals( WPML_IGNORE_CUSTOM_FIELD, $ignored_setting->status() );
			$this->assertTrue( $ignored_setting->is_read_only() );
			$this->assertTrue( $ignored_setting->excluded() );
		}
	}
}
