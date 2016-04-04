<?php
require_once 'test-url-converter.php';

class Test_WPML_Lang_Param_Converter extends WPML_UnitTestCase {
	public function test_convert_url() {
		$default_lang_code = wpml_get_setting_filter( false, 'default_language' );
		$this->check_lang_recognition_root( $default_lang_code );
		$this->check_lang_recognition_subdir( $default_lang_code );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1785
	 */
	public function test_paginated_link_filter() {
		$converter     = $this->switch_to_langs_as_params();
		$example_links = array(
			'<a href="http://example.com/example-page?lang=de/2/"><span><span class="screen-reader-text">Seite </span>2</span></a>',
			'<a href="http://example.com/example-page/?lang=de/2/"><span><span class="screen-reader-text">Seite </span>2</span></a>'
		);
		$correct_link  = '<a href="http://example.com/example-page/2/?lang=de"><span><span class="screen-reader-text">Seite </span>2</span></a>';

		foreach ( $example_links as $example ) {
			$this->assertEquals( $correct_link, $converter->paginated_link_filter( $example ) );
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1919
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2260
	 */
	function test_paginated_url_filter() {
		$converter    = $this->switch_to_langs_as_params();
		$correct_link = 'http://cleanwp.dev/page/2/category/uncategorized/?lang=en';
		$example_urls = array(
				'http://cleanwp.dev/page/2/?lang=en/category/uncategorized/',
				'http://cleanwp.dev?lang=en/page/2/category/uncategorized/?lang=en',
				'http://cleanwp.dev/page/2/category/uncategorized/?lang=en/',
				'http://cleanwp.dev/page/2/category/uncategorized/?lang=en%2F',
		);

		foreach ( $example_urls as $example ) {
			$this->assertEquals( $correct_link, $converter->paginated_url_filter( $example ) );
		}
		$this->assertEquals( $correct_link, $converter->paginated_url_filter( $correct_link ) );
	}

	private function check_lang_recognition_root( $default_lang_code ) {
		//todo: refactor code in order to inject dependencies
		global $sitepress;

		$sitepress_settings = $sitepress->get_settings();
		icl_set_setting( 'language_negotiation_type', 3, true );
		$converter = load_wpml_url_converter(
			$sitepress_settings,
			3,
			$default_lang_code
		);
		$abs_home  = $converter->get_abs_home();

		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://example.org/?lang=fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://example.org?lang=fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'example.org?bla=foo&lang=fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( '/?bla=foo&lang=fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://example.org/?bla=foo&lang=fr/' ) );

		$this->assertEquals( trailingslashit( $abs_home ) . '?lang=de', $converter->convert_url( $abs_home . '/?lang=fr', 'de' ) );
		$this->assertEquals( $abs_home . '?lang=de', $converter->convert_url( $abs_home . '?lang=fr', 'de' ) );
		$this->assertEquals( $abs_home . '?lang=fr', $converter->convert_url( $abs_home, 'fr' ) );
		$this->assertEquals( trailingslashit( $abs_home ) . 'fooo?lang=de', $converter->convert_url( trailingslashit( $abs_home ) . 'fooo', 'de' ) );
		$this->assertEquals( trailingslashit( $abs_home ) . 'fooo/?lang=de', $converter->convert_url( trailingslashit( $abs_home ) . 'fooo/', 'de' ) );

		$sitepress->switch_lang( $default_lang_code );
		$this->assertEquals( $abs_home, $converter->convert_url( $abs_home, '' ) );
		$this->assertEquals(
			trailingslashit( $abs_home ) . '?lang=fr',
			$converter->convert_url( trailingslashit( $abs_home ), 'fr' )
		);
		$this->assertEquals(
			trailingslashit( $abs_home ) . '?foo=bar&lang=fr',
			$converter->convert_url( trailingslashit( $abs_home ) . '?foo=bar', 'fr' )
		);
		$this->assertEquals(
			trailingslashit( $abs_home ) . '?foo=bar&bar=foo&lang=fr',
			$converter->convert_url( trailingslashit( $abs_home ) . '?foo=bar&bar=foo', 'fr' )
		);
	}

	private function check_lang_recognition_subdir( $default_lang_code ) {
		//todo: refactor code in order to inject dependencies
		global $sitepress;
		global $wpdb;

		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => 'http://www.example.com/testaroo' ),
			array( 'option_name' => 'home' )
		);

		$sitepress_settings = $sitepress->get_settings();
		$converter = load_wpml_url_converter(
			$sitepress_settings,
			3,
			$default_lang_code
		);

		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://example.org/?lang=fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://example.org/?lang=fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'example.org/fr?lang=fr' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( '/fr?bla=meh&lang=fr' ) );
	}

	public function test_get_language_from_url() {
		//todo: refactor code in order to inject dependencies
		global $sitepress;
		global $wpdb;

		icl_set_setting( 'language_negotiation_type', 1, true );
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => 'http://www.example.com/testaroo' ),
			array( 'option_name' => 'home' )
		);

		$sitepress_settings = $sitepress->get_settings();
		$converter = load_wpml_url_converter(
			$sitepress_settings,
			3,
			icl_get_setting( 'default_language' )
		);

		$this->assertEquals( 'en', $converter->get_language_from_url( 'http://www.example.com/testaroo' ) );
		$this->assertEquals( 'de', $converter->get_language_from_url( 'http://www.example.com/testaroo/?lang=de' ) );
		$this->assertEquals( 'fr', $converter->get_language_from_url( 'http://www.example.com/testaroo/?lang=fr' ) );
	}

	public function test_permalink_filter() {
		//todo: refactor code in order to inject dependencies
		global $sitepress;
		global $wpml_post_translations, $wpml_url_filters;
		icl_set_setting( 'language_negotiation_type', 3, true );
		$newhome = $this->set_subdir_path('www.example.com', '/testaroo');

		$sitepress_settings = $sitepress->get_settings();
		$converter = load_wpml_url_converter(
			$sitepress_settings,
			3,
			icl_get_setting( 'default_language' )
		);
		$new_home_admin = trailingslashit( $newhome ) . '/wp_admin/';
		$this->assertEquals( 'de',
							 $converter->get_language_from_url( $new_home_admin . 'test.php?lang=de' ) );
		$this->assertEquals( 'fr',
							 $converter->get_language_from_url( $new_home_admin . 'test.php?lang=fr' ) );
		$this->assertEquals( 'en',
							 $converter->get_language_from_url( $new_home_admin . 'wp-admin/test.php' ) );

		$this->set_subdir_path('www.example.com', '/');
		$converter = load_wpml_url_converter(
			$sitepress_settings,
			3,
			icl_get_setting( 'default_language' )
		);

		$this->assertEquals( 'de',
							 $converter->get_language_from_url( 'http://www.example.com/wp-admin/test.php?lang=de' ) );
		$this->assertEquals( 'fr',
							 $converter->get_language_from_url( 'http://www.example.com/wp-admin/test.php?lang=fr' ) );
		$this->assertEquals( 'en', $converter->get_language_from_url( 'http://www.example.com/wp-admin/test.php' ) );

		$test_slug   = 'testslugandparam';
		$new_post_id = wpml_test_insert_post( 'de', 'post', false, $test_slug );

		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( "%postname%" );
		create_initial_taxonomies();
		$wp_rewrite->flush_rules();
		$this->flush_cache();
		$wpml_url_filters = new WPML_URL_Filters( $wpml_post_translations, $converter, $sitepress );

		$link = trailingslashit( $converter->get_abs_home() ) . $test_slug . '?lang=de';

		$this->assertEquals(
			$link,
			get_permalink( $new_post_id )
		);

		$this->assertEquals( array(), $converter->request_filter( array( 'lang' => 'bla' ) ) );
		$this->assertEquals( array( 'foo' => '' ),
							 $converter->request_filter( array( 'lang' => 'bla', 'foo' => '' ) ) );
		$example_long = array( 'test' => 'meh', 'lang' => 'bla' );
		$this->assertEquals( $example_long, $example_long );
	}
}