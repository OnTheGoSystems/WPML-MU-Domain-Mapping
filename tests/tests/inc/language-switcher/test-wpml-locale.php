<?php

class Test_WPML_Locale extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1648
	 */
	public function test_filter_sanitize_title() {
		global $wpml_language_resolution, $sitepress;

		$this->assertTrue( $wpml_language_resolution->is_language_active( 'de' ) || $wpml_language_resolution->is_language_active( 'dk' ),
						   'This tests requires the test setup to use German or Danish as on of the active languages!' );

		$sitepress->locale_utils->init();
		$this->assertTrue( has_filter( 'sanitize_title' ) );
		$this->assertEquals( 'hoejden', sanitize_title( 'HØJDEN' ) );
	}

	public function test_locale() {
		global $wpdb;

		$tested_lang     = 'de';
		$expected_locale = 'de_DE';
		foreach ( array( null, 'mock_action' ) as $action ) {
			foreach ( array( true, false ) as $admin ) {
				foreach ( array( true, false ) as $ajax ) {
					if ( $action ) {
						$_REQUEST['action'] = $action;
						$_REQUEST['lang']   = $tested_lang;
					} else {
						$_REQUEST['action'] = null;
						$_REQUEST['lang']   = null;
					}
					$wp_api = $this->get_wp_api_mock();
					$wp_api->method( 'is_ajax' )->willReturn( $ajax );
					$wp_api->method( 'is_admin' )->willReturn( $admin );
					$sitepress = $this->get_sitepress_mock( $wp_api );
					$sitepress->method( 'get_current_language' )->willReturn( $tested_lang );
					$sitepress->method( 'user_lang_by_authcookie' )->willReturn( $tested_lang );
					$subject = new WPML_Locale( $wpdb, $sitepress, $expected_locale );
					$this->assertEquals( $expected_locale, $subject->locale() );
				}
			}
		}
		$_REQUEST['action'] = null;
		$_REQUEST['lang']   = null;

		$theme_lang_folders = array( 'a', 'b', 'c' );
		$theme_domain       = rand_str();
		$wp_api             = $this->get_wp_api_mock();

		$wp_api->expects( $this->exactly(3) )
		       ->method( 'load_textdomain' )
		       ->withConsecutive(
			       array(
				       $theme_domain,
				       $theme_lang_folders[0] . '/' . $expected_locale . '.mo'
			       ),
			       array(
				       $theme_domain,
				       $theme_lang_folders[1] . '/' . $expected_locale . '.mo'
			       ),
			       array(
				       $theme_domain,
				       $theme_lang_folders[2] . '/' . $expected_locale . '.mo'
			       )
		       );

		$sitepress = $this->get_sitepress_mock( $wp_api );
		$sitepress->method( 'get_current_language' )->willReturn( $tested_lang );
		$sitepress->method( 'get_setting' )->willReturnMap(
			array(
				array( 'theme_localization_load_textdomain', false, true ),
				array( 'gettext_theme_domain_name',false, $theme_domain ),
				array( 'theme_language_folders', false, $theme_lang_folders )
			) );
		$subject = new WPML_Locale( $wpdb, $sitepress, $expected_locale );
		$subject->locale();
		$subject->locale();
	}
	
	public function test_get_locale() {
		global $wpdb, $sitepress, $locale;
		
		$code = 'en';
		
		$locale_class = new WPML_Locale( $wpdb, $sitepress, $locale );
		$locale = $locale_class->get_locale( $code );
		// Make sure it's cached.
		
		$found  = false;
		$cache_key = 'get_locale' . $code;
		$cache  = new WPML_WP_Cache( '' );
		$this->assertEquals( $locale, $cache->get( $cache_key, $found ) );
		
		$this->assertTrue( $found );
		$this->assertFalse( $locale_class->get_locale( null ) );
	}
}