<?php
if ( ! defined( 'WP_TESTS_MULTISITE' ) || ! WP_TESTS_MULTISITE ) {
	require_once 'test-url-converter.php';

	class Test_WPML_Lang_Domains_Converter extends WPML_UnitTestCase {
		/** @var  WPML_Lang_Domains_Converter $converter */
		private $converter;
		private $sec_domain = 'http://example.de';

		private $languages_urls;

		public function setUp() {
			parent::setUp();
			$this->languages_urls = array(
				'de' => $this->sec_domain,
				'fr' => 'http://example.fr',
				'it' => 'http://it.example.sub',
				'ru' => 'http://318example.sub.ru',
				'es' => 'http://es.example.com',
			);
			icl_set_setting( 'language_domains', $this->languages_urls, true );
			icl_set_setting( 'language_negotiation_type', 2, true );
			$this->reload_converter();
		}

		/**
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1908
		 */
		public function test_get_language_from_url() {
			$schemes = array( 'http://', 'https://', '' );
			$url_suffixes = array(
				'' => 'current',
				'/' => 'current',
				'/foo' => 'current',
				'/bar' => 'current',
				rand_str(5) => 'default',
				'?test=one' => 'current',
				'#test-one' => 'default',
				'/#test-one' => 'current',
			);
			$converter = $this->converter;
			foreach($schemes as $scheme) {
				foreach($url_suffixes as $url_suffix => $expected_result) {
					foreach($this->languages_urls as $language_code => $url) {
						$url_without_scheme = str_replace('http://', '', $url);

						$expected_language = $expected_result == 'current' ?  $language_code : 'en';

						$test_url = $scheme . $url_without_scheme . $url_suffix;
						$actual_language = $converter->get_language_from_url( $test_url );
						$this->assertEquals( $expected_language, $actual_language );
					}
				}
			}

			$this->check_domains_and_subdir();
		}

		/**
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-328
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2386
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2596
		 */
		public function test_convert_url() {
			$converter = $this->converter;
			foreach ( array( 'http://', 'https://', '' ) as $schema ) {
				set_current_screen( 'front' );
				$this->assertEquals( $schema . 'example.fr/', $converter->convert_url( $schema . 'example.org/', 'fr' ) );
				$this->assertEquals( $schema . 'example.fr/', $converter->convert_url( $schema . 'www.example.org/', 'fr' ) );
				$this->assertEquals( $schema . 'example.de/', $converter->convert_url( $schema . 'example.org/', 'de' ) );
				$this->assertEquals( $schema . 'example.de/', $converter->convert_url( $schema . 'www.example.org/', 'de' ) );
				$this->assertEquals( $schema . 'example.de', $converter->convert_url( $schema . 'example.org', 'de' ) );
				$this->assertEquals( $schema . 'example.de', $converter->convert_url( $schema . 'www.example.org', 'de' ) );
				$this->assertEquals( $schema . 'example.de/', $converter->convert_url( $schema . 'example.fr/', 'de' ) );
				$this->assertEquals( $schema . 'example.de', $converter->convert_url( $schema . 'example.fr', 'de' ) );
				$this->assertEquals( $schema . 'example.org/', $converter->convert_url( $schema . 'example.fr/', 'en' ) );
				$this->assertEquals( $schema . 'www.example.org/', $converter->convert_url( $schema . 'www.example.org/', 'en' ) );
				$this->assertEquals( $schema . 'example.fr/', $converter->convert_url( $schema . 'example.fr/', 'fr' ) );
				$this->assertEquals( $schema . '318example.sub.ru/', $converter->convert_url( $schema . 'example.org/', 'ru' ) );
				$this->assertEquals( $schema . '318example.sub.ru/', $converter->convert_url( $schema . '318example.org/', 'ru' ) );

				$admin_url = $schema . 'example.org/wp-admin/plugins.php';
				$this->assertEquals( $schema . 'example.fr/wp-admin/plugins.php',
					$converter->convert_url( $admin_url, 'fr' ) );

				set_current_screen( 'dashboard' );
				$this->reload_converter();
				wp_cache_init();
				$admin_url = $schema . 'example.org/wp-admin/plugins.php';
				$this->assertEquals( $admin_url, $converter->convert_url( $admin_url, 'fr' ) );

				// Check for multi-level sub domains.
				$this->languages_urls = array(
					'ru' => 'http://318example.318sub.ru',
					'de' => 'http://318example.318sub.318sub.ru',
				);
				icl_set_setting( 'language_domains', $this->languages_urls, true );
				icl_set_setting( 'language_negotiation_type', 2, true );
				$this->reload_converter();
				$this->assertEquals( $schema . '318example.318sub.ru/' , $this->converter->convert_url( $schema . 'example.org/', 'ru' ) );
				$this->assertEquals( $schema . '318example.318sub.318sub.ru/' , $this->converter->convert_url( $schema . 'example.org/', 'de' ) );
			}
		}

		/**
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/iclsupp-464
		 */
		public function test_url_to_postid() {
			//todo: refactor code in order to inject dependencies
			global $sitepress;

			$sitepress->set_setting(
				'language_domains',
				array_fill_keys( array_keys( $sitepress->get_active_languages() ), '' ),
				true );
			$pid       = wpml_test_insert_post( 'de' );
			$permalink = get_permalink( $pid );
			$this->assertEquals( $permalink, $sitepress->url_to_postid( get_permalink( $pid ) ) );
		}

		/**
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1555
		 */
		public function test_trailing_slash_in_option() {
			//todo: refactor code in order to inject dependencies
			global $sitepress;
			global $wpdb;
			global $wpml_include_url_filter;

			$sitepress->switch_lang( $sitepress->get_default_language() );
			$home = get_home_url();
			set_current_screen( 'front' );

			$wpdb->update( $wpdb->options,
				array( 'option_value' => trailingslashit( $home ) ),
				array( 'option_name' => 'home' ) );
			wp_cache_init();
			$this->reload_converter();

			$this->assertEquals( trailingslashit( $this->sec_domain ) . 'main-sitemap.xsl',
				$this->converter->convert_url( trailingslashit( $home ) . 'main-sitemap.xsl', 'de' ) );
			$wpml_include_url_filter = new WPML_Include_Url( $wpdb, parse_url( $home, PHP_URL_HOST ) );
			$this->reload_converter();
			$this->assertEquals( trailingslashit( $home ), $this->converter->get_abs_home() );
			$this->assertEquals( trailingslashit( $this->sec_domain ), $this->converter->convert_url( $this->converter->get_abs_home(), 'de' ) );
			$wpml_include_url_filter = null;
		}

		private function reload_converter() {
			//todo: refactor code in order to inject dependencies
			global $sitepress;

			$this->converter = load_wpml_url_converter( $sitepress->get_settings(), '', $sitepress->get_default_language() );
		}

		/**
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1978
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2449
		 */
		private function check_domains_and_subdir() {
			foreach (
				array(
					'',
					'/hello-world',
					'/slug/foo/bar'
				) as $second_slug
			) {
				$urls = array(
					'de' => 'de.subdir.dev/subdir' . $second_slug,
					'it' => 'it.subdir.dev/subdir' . $second_slug,
					'en' => WP_TESTS_DOMAIN . '/subdir' . $second_slug
				);
				icl_set_setting( 'language_domains', $urls, true );
				$wpml_wp_api = new WPML_WP_API();
				$subject     = new WPML_Lang_Domains_Converter( $urls, 'en',
					array_keys( $urls ), $wpml_wp_api );
				foreach ( $urls as $lang_code => $url ) {
					$this->assertEquals( $lang_code,
						$subject->get_language_from_url( trailingslashit( $url ) . rand_str( 10 ) ) );
				}
				$lang_codes = array_keys( $urls );
				foreach ( $lang_codes as $source ) {
					foreach ( $lang_codes as $target ) {
						$this->assertEquals( $urls[ $target ], $subject->convert_url( $urls[ $source ], $target ) );
					}
				}
			}
		}

		/**
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2449
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2465
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2089
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2527
		 */
		function test_admin_url_filter_front_end() {
			//todo: refactor code in order to inject dependencies
			global $sitepress;

			$wpml_wp_api = $this->get_wp_api_mock();
			$wpml_wp_api->method( 'is_front_end' )->willReturn( true );
			$subject          = new WPML_Lang_Domains_Converter( $this->languages_urls,
				'en', array_keys( $this->languages_urls ), $wpml_wp_api );
			$active_languages = $sitepress->get_active_languages();
			foreach ( $active_languages as $lang_code => $language_data ) {
				$sitepress->switch_lang( $lang_code );
				$expected_language_url = 'http://example.com/' . rand_str( 10 );
				$this->assertEquals( $expected_language_url,
					$subject->admin_url_filter( $expected_language_url, '' ) );
			}

			/** @var array $languages_domains */
			foreach ( $active_languages as $lang_code => $url ) {
				$sitepress->switch_lang( $lang_code );
				$rand_string           = rand_str( 10 );
				$expected_language_url = 'http://example.com/' . $rand_string;
				if ( $lang_code !== $sitepress->get_default_language() ) {
					$expected_language_url = $this->languages_urls[ $lang_code ] . '/' . $rand_string;
				}
				$this->assertEquals( $expected_language_url,
					$subject->admin_url_filter( 'http://example.com/' . $rand_string,
						'admin-ajax.php' ) );
				$url_relative = '/' . $rand_string;
				$this->assertEquals( $url_relative,
					$subject->admin_url_filter( $url_relative,
						'admin-ajax.php' ) );
			}
		}

		function test_admin_url_filter_back_end() {
			//todo: refactor code in order to inject dependencies
			global $sitepress;

			$wpml_wp_api = $this->get_wp_api_mock();
			$wpml_wp_api->method( 'is_front_end' )->willReturn( false );
			$subject = new WPML_Lang_Domains_Converter( $this->languages_urls,
				'en', array_keys( $sitepress->get_active_languages() ),
				$wpml_wp_api );
			$active_languages = $sitepress->get_active_languages();
			foreach ( $active_languages as $lang_code => $language_data ) {
				$sitepress->switch_lang( $lang_code );
				$expected_language_url = 'http://example.com/' . rand_str( 10 );
				$actual_language_url   = $subject->admin_url_filter( $expected_language_url, '' );
				$this->assertEquals( $expected_language_url, $actual_language_url );
			}

			/** @var array $languages_domains */
			foreach ( $active_languages as $lang_code => $url ) {
				$sitepress->switch_lang( $lang_code );
				$rand_string           = rand_str( 10 );
				$expected_language_url = 'http://example.com/' . $rand_string;

				// See dd72c16aac9f52ed103578a96c540ba66095f1b1
				// $expected_language_url = add_query_arg( array( 'lang' => $lang_code ), $expected_language_url );
				$actual_language_url   = $subject->admin_url_filter( 'http://example.com/' . $rand_string, 'admin-ajax.php' );
				$this->assertEquals( $expected_language_url, $actual_language_url );
			}
		}
	}
}