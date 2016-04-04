<?php

class TestWPML_Installation extends WPML_UnitTestCase {

	/** @var  WPML_Installation $installation_instance */
	private $installation_instance;
	private $test_language = 'en';
	private $test_active_languages;

	function setUp() {
		parent::setUp();
		icl_set_setting( 'setup_complete', 0, true );
		$this->installation_instance = wpml_get_setup_instance();
		$this->test_active_languages = explode( ',', WPML_TEST_LANGUAGE_CODES );
	}

	function test_go_to_setup1() {
		global $wpdb, $sitepress;

		$this->installation_instance->go_to_setup1();

		$settings = get_option( 'icl_sitepress_settings' );
		$this->assertArrayNotHasKey( 'default_categories', $settings );
		$this->assertArrayNotHasKey( 'default_language', $settings );
		$this->assertArrayNotHasKey( 'setup_wizard_step', $settings );
		$this->assertEquals( 0, $settings['existing_content_language_verified'] );
		$this->assertEqualSets( array(), $settings['active_languages'] );
		$this->assertEmpty( $settings['active_languages'] );
		$this->assertEquals( 0, $wpdb->get_var( " SELECT COUNT(*) FROM {$wpdb->prefix}icl_translations" ) );
		$this->assertEquals( 0, $wpdb->get_var( " SELECT COUNT(*) FROM {$wpdb->prefix}icl_locale_map" ) );
		$this->assertEquals( 0,
							 $wpdb->get_var( " SELECT COUNT(*) FROM {$wpdb->prefix}icl_languages WHERE active = 1" ) );
	}

	function test_finish_step1_and_go_back() {
		$rtl_language  = 'ar';
		$test_language = $this->test_language;
		$post_factory  = new WP_UnitTest_Factory_For_Post();
		$term_factory  = new WP_UnitTest_Factory_For_Term();
		$this->posts_num     = mt_rand( 2, 4 );
		$this->terms_num     = mt_rand( 2, 4 );
		$post_factory->create_many( $this->posts_num );
		$term_factory->create_many( $this->terms_num );

		/**
		 * Start with one language
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2727
		 */
		$this->start_with_a_wrong_default_language( $rtl_language );

		// Then get back and start again with the correct test language
		$this->go_back_and_set_the_right_default_language( $test_language );
	}

	private function start_with_a_wrong_default_language( $rtl_language ) {
		global $sitepress, $locale, $wp_locale;
		$this->installation_instance->go_to_setup1();
		$this->installation_instance->finish_step1( $rtl_language );
		$this->assertEquals( $rtl_language, wpml_get_setting_filter( false, 'default_language' ) );
		$this->assertEquals( $rtl_language, $sitepress->get_default_language() );
		$this->assertEquals( $sitepress->get_locale( $rtl_language ), $locale );
		$this->assertTrue( $wp_locale->is_rtl() );
	}

	private function go_back_and_set_the_right_default_language( $test_language ) {
		global $wpdb, $sitepress, $locale, $wp_locale;
		$this->installation_instance->go_to_setup1();
		$this->installation_instance->finish_step1( $test_language );
		$this->assertEquals( 1, wpml_get_setting_filter( false, 'existing_content_language_verified' ) );
		$this->assertEquals( 2, wpml_get_setting_filter( false, 'setup_wizard_step' ) );
		$this->assertEquals( $this->posts_num,
			$wpdb->get_var( " SELECT COUNT(*) FROM {$wpdb->prefix}icl_translations WHERE language_code = '{$this->test_language}' AND element_type LIKE 'post_%'" ) );
		$this->assertEquals( $this->terms_num,
			$wpdb->get_var( " SELECT COUNT(*) FROM {$wpdb->prefix}icl_translations WHERE language_code = '{$this->test_language}' AND element_type LIKE 'tax%'" ) );
		$this->assertEquals( $test_language, wpml_get_setting_filter( false, 'default_language' ) );
		$this->assertEquals( $test_language, $sitepress->get_default_language() );
		$this->assertGreaterThan( 1, did_action( 'icl_initial_language_set' ) );
		$this->assertEquals( 10, has_filter( 'locale', array( $sitepress, 'locale' ) ) );
		$this->assertEquals( $sitepress->get_locale( $test_language ), $locale );
		$this->assertFalse( $wp_locale->is_rtl() );
	}

	function test_finish_step2() {
		global $wpdb;

		$post_factory = new WP_UnitTest_Factory_For_Post();
		$post_factory->create_many( 2 );
		$this->installation_instance->go_to_setup1();
		$this->installation_instance->finish_step1( $this->test_language );
		$this->installation_instance->finish_step2( $this->test_active_languages );

		$langs_in_db     = $wpdb->get_col( "SELECT code FROM {$wpdb->prefix}icl_languages WHERE active = 1" );
		$langs_in_db_tmp = $langs_in_db;
		foreach ( $this->test_active_languages as $lang ) {
			$this->assertContains( $lang, $langs_in_db );
			$langs_in_db_tmp = array_diff( $langs_in_db_tmp, array( $lang ) );
		}

		$this->assertEmpty( $langs_in_db_tmp );
		$this->assertEquals( 1, wpml_get_setting_filter( false, 'existing_content_language_verified' ) );
		$this->assertEquals( 3, wpml_get_setting_filter( false, 'setup_wizard_step' ) );
		global $sitepress;
		$active_langs_sp = $sitepress->get_active_languages();
		foreach ( $this->test_active_languages as $lang ) {
			$this->assertArrayHasKey( $lang, $active_langs_sp );
		}
		$this->assertEquals( true, wpml_get_setting_filter( false, 'dont_show_help_admin_notice' ) );
	}

	function test_finish_step3() {
		$this->installation_instance->go_to_setup1();
		$this->installation_instance->finish_step1( $this->test_language );
		$this->installation_instance->finish_step2( $this->test_active_languages );
		$ls_options = array(
			'icl_lso_link_empty'   => 0,
			'icl_lso_flags'        => 1,
			'icl_lso_native_lang'  => 1,
			'icl_lso_display_lang' => 1
		);
		$this->installation_instance->finish_step3( false, $ls_options );

		$this->assertEquals( 4, wpml_get_setting_filter( false, 'setup_wizard_step' ) );
		$this->assertEquals( 0, wpml_get_setting_filter( false, 'icl_lso_link_empty' ) );
		$this->assertEquals( 1, wpml_get_setting_filter( false, 'icl_lso_flags' ) );
		$this->assertEquals( 1, wpml_get_setting_filter( false, 'icl_lso_native_lang' ) );
		$this->assertEquals( 1, wpml_get_setting_filter( false, 'icl_lso_display_lang' ) );
	}

	function test_finish_installation() {
		$this->installation_instance->go_to_setup1();
		$this->installation_instance->finish_step1( $this->test_language );
		$this->installation_instance->finish_step2( $this->test_active_languages );
		$ls_options = array(
			'icl_lso_link_empty'   => 0,
			'icl_lso_flags'        => 1,
			'icl_lso_native_lang'  => 1,
			'icl_lso_display_lang' => 1
		);
		$this->installation_instance->finish_step3( false, $ls_options );
		$this->installation_instance->finish_installation();
		$this->assertEquals( 1, wpml_get_setting_filter( false, 'setup_complete' ) );
		$this->assertFalse( wpml_get_setting_filter( false, 'site_key' ) );
		$site_key = rand_str();
		$this->installation_instance->finish_installation( $site_key );
		$this->assertEquals( $site_key, wpml_get_setting_filter( false, 'site_key' ) );
	}
}

