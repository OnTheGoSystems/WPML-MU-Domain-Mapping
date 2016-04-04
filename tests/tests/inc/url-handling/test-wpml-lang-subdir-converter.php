<?php
require_once 'test-url-converter.php';

class Test_WPML_Lang_Subdir_Converter extends WPML_UnitTestCase {
	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1672
	 */
	function test_convert_url() {
		//todo: refactor code in order to inject dependencies
		global $sitepress;
		global $wpdb;

		$sitepress_settings = $sitepress->get_settings();

		icl_set_setting( 'language_negotiation_type', 1, true );
		$default_lang_code                                            = wpml_get_setting_filter( false, 'default_language' );
		$sitepress_settings['urls']['directory_for_default_language'] = 0;
		$converter                                                    = load_wpml_url_converter(
			$sitepress_settings,
			1,
			$default_lang_code
		);
		$abs_home                                                     = $converter->get_abs_home();

		$this->check_fallback_url( $converter );

		foreach ( array( 'http://example.org/fr/', 'http://example.org/fr', 'example.org/fr', '/fr' ) as $url ) {
			$this->assertEquals( 'fr', $converter->get_language_from_url( $url ) );
		}

		$examples = array(
			array( '/de/', '/fr/' ),
			array( '/de', '/fr' ),
			array( '/de', '' ),
			array( '/de/fooo', '/fooo' ),
			array( '/de/fooo/', '/fooo/' )
		);
		foreach ( $examples as $example ) {
			$this->assertEquals( $abs_home . $example[0], $converter->convert_url( $abs_home . $example[1], 'de' ) );
		}

		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => 'http://www.example.com/testaroo' ),
			array( 'option_name' => 'home' )
		);

		$converter = load_wpml_url_converter(
			$sitepress_settings,
			1,
			$default_lang_code
		);

		$abs_home = $converter->get_abs_home();

		$this->assertEquals( $abs_home . '/de/', $converter->convert_url( $abs_home . '/fr/', 'de' ) );
		$this->assertEquals( $abs_home . '/de', $converter->convert_url( $abs_home . '/fr', 'de' ) );
		$this->assertEquals( $abs_home . '/de', $converter->convert_url( $abs_home, 'de' ) );
		$this->assertEquals( $abs_home . '/de/fooo', $converter->convert_url( $abs_home . '/fooo', 'de' ) );
		$this->assertEquals( $abs_home . '/de/fooo/', $converter->convert_url( $abs_home . '/fooo/', 'de' ) );
		$this->assertEquals( trailingslashit( $abs_home ), $converter->convert_url( $abs_home . '/fr/', $default_lang_code ) );
		$this->assertEquals( $abs_home, $converter->convert_url( $abs_home . '/fr', $default_lang_code ) );
		$this->assertEquals( $abs_home . '/foo', $converter->convert_url( $abs_home . '/fr/foo', $default_lang_code ) );
		$this->assertEquals( $abs_home . '/baaaaa', $converter->convert_url( $abs_home . '/fr/baaaaa', $default_lang_code ) );
		$this->assertEquals( $abs_home, $converter->convert_url( $abs_home, $default_lang_code ) );
		$this->assertEquals( $abs_home . '/fooo', $converter->convert_url( $abs_home . '/fooo', $default_lang_code ) );
		$this->assertEquals( $abs_home . '/fooo/', $converter->convert_url( $abs_home . '/fooo/', $default_lang_code ) );

		// Root page
		$sitepress_settings['urls']['directory_for_default_language'] = 1;
		$converter                                                    = load_wpml_url_converter(
			$sitepress_settings,
			1,
			$default_lang_code
		);
		wp_cache_init();

		$this->assertEquals( $abs_home . '/fr', $converter->convert_url( $abs_home, 'fr' ) );
		$this->assertEquals( $abs_home . '/fr/foo', $converter->convert_url( $abs_home . '/en/foo', 'fr' ) );
		$this->assertEquals( $abs_home . '/fr/foo/', $converter->convert_url( $abs_home . '/en/foo/', 'fr' ) );
		$this->assertEquals(
			$abs_home . '/' . $default_lang_code,
			$converter->convert_url( $abs_home, $default_lang_code )
		);
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1498
	 */
	function test_get_language_from_url() {
		//todo: refactor code in order to inject dependencies
		global $sitepress;
		global $wpdb;

		$sitepress_settings = $sitepress->get_settings();

		icl_set_setting( 'language_negotiation_type', 1, true );
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => 'http://www.example.com/testaroo' ),
			array( 'option_name' => 'home' )
		);
		$sitepress_settings[ 'urls' ][ 'directory_for_default_language' ] = 0;

		$converter = load_wpml_url_converter(
			$sitepress_settings,
			1,
			icl_get_setting( 'default_language' )
		);

		$this->assertEquals( 'en', $converter->get_language_from_url( 'http://www.example.com/testaroo' ) );
		$this->assertEquals( 'de', $converter->get_language_from_url( 'http://www.example.com/testaroo/de' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://www.example.com/testaroo/fr?s=bla' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://www.example.com/testaroo/fr' ) );

		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://example.org/fr/?s=bla' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://example.org/fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'example.org/fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'example.org/fr?s=bla' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( '/fr' ) );
	}

	public function test_permalink_filter(){
		//todo: refactor code in order to inject dependencies
		global $sitepress;
		global $wpdb;

		$sitepress_settings = $sitepress->get_settings();

		icl_set_setting ( 'language_negotiation_type', 1, true );
		$wpdb->update (
			$wpdb->options,
			array( 'option_value' => 'http://www.example.com/testaroo' ),
			array( 'option_name' => 'home' )
		);
		$converter = load_wpml_url_converter (
			$sitepress_settings,
			1,
			icl_get_setting ( 'default_language' )
		);

		$this->assertEquals ( 'de', $converter->get_language_from_url ( 'http://www.example.com/testaroo/wp-admin/test.php?lang=de' ) );
		$this->assertEquals ( 'fr', $converter->get_language_from_url ( 'http://www.example.com/testaroo/wp-admin/test.php?lang=fr' ) );
		$this->assertEquals ( 'en', $converter->get_language_from_url ( 'http://www.example.com/testaroo/wp-admin/test.php' ) );

		$wpdb->update (
			$wpdb->options,
			array( 'option_value' => 'http://www.example.com' ),
			array( 'option_name' => 'home' )
		);
		$converter = load_wpml_url_converter (
			$sitepress_settings,
			1,
			icl_get_setting ( 'default_language' )
		);

		$this->assertEquals ( 'de', $converter->get_language_from_url ( 'http://www.example.com/wp-admin/test.php?lang=de' ) );
		$this->assertEquals ( 'fr', $converter->get_language_from_url ( 'http://www.example.com/wp-admin/test.php?lang=fr' ) );
		$this->assertEquals ( 'en', $converter->get_language_from_url ( 'http://www.example.com/wp-admin/test.php' ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1811
	 *
	 * @param WPML_URL_Converter $converter
	 */
	private function check_fallback_url( WPML_URL_Converter $converter ) {
		//todo: refactor code in order to inject dependencies
		global $wpdb;

		$new_home = 'http://' . rand_str() . '/sub';
		$wpdb->update( $wpdb->options, array( 'option_value' => $new_home ), array( 'option_name' => 'home' ) );
		wp_cache_init();
		$this->assertEquals( $new_home . '/fr', $converter->convert_url( $new_home, 'fr' ) );
	}
}