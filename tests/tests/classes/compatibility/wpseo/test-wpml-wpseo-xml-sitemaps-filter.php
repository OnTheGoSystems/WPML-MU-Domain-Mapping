<?php

class Test_WPML_WPSEO_XML_Sitemaps_Filter extends WPML_UnitTestCase {

	/**
	 * Test exclude function for hidden languages.
	 */
	public function test_sitemap_exclude_hidden_language_posts() {
		global $wpml_url_converter;
		foreach (
			array(
				array(),
				array( 'fr' ),
				array( 'de', 'fr' ),
			) as $hidden_langs
		) {
			// Mock SitePress object.
			$sitepress_mock = $this->get_sitepress_mock();

			// Add hidden languages.
			$sitepress_mock->method( 'get_setting' )->willReturnMap( array(
				array( 'hidden_languages', array(), $hidden_langs ),
			) );

			// Set post language.
			$pt_mock = $this->get_post_translation_mock();
			$pt_mock->method( 'get_element_lang_code' )->willReturn( 'de' );

			$sitepress_mock->method( 'post_translations' )->willReturn( $pt_mock );

			// Create minimal post object.
			$post = new stdClass;
			$post->ID = 0;

			// Instantiate WPSEO_XML_Sitemaps_Filter class
			$stub = new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress_mock, $wpml_url_converter );

			// Check expected results
			if ( in_array( 'de', $hidden_langs ) ) {
				$this->assertEquals( '', $stub->exclude_hidden_language_posts( 'http://example.com', $post ) );
				unset( $post->ID );
				$this->assertEquals( 'http://example.com', $stub->exclude_hidden_language_posts( 'http://example.com', $post ) );
			} else {
				$this->assertEquals( 'http://example.com', $stub->exclude_hidden_language_posts( 'http://example.com', $post ) );
			}
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2523
	 */
	function test_terms_clauses_filter_with_language_negociation() {
		// Need to check if the filter Sitepress:terms_clauses() is removed
		// on sitemap generation when we have languages in directories or as parameter.
		global $sitepress, $sitepress_settings, $wpml_url_converter;

		new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress, $wpml_url_converter );

		// Check with languages in directories first
		$sitepress_settings['language_negotiation_type'] = 1;
		$sitepress->set_term_filters_and_hooks();
		$this->apply_filters_before_sitemap_generation();
		$filtered = has_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
		$this->assertFalse( (bool) $filtered );

		// Check with languages in domains
		$sitepress_settings['language_negotiation_type'] = 2;
		$sitepress->set_term_filters_and_hooks();
		$this->apply_filters_before_sitemap_generation();
		$filtered = has_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
		$this->assertEquals( 10, $filtered );

		// Check with languages as parameter
		$sitepress_settings['language_negotiation_type'] = 3;
		$sitepress->set_term_filters_and_hooks();
		$this->apply_filters_before_sitemap_generation();
		$filtered = has_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
		$this->assertFalse( (bool) $filtered );

		// Check with languages in directories and root page
		$sitepress_settings['language_negotiation_type'] = 1;
		$sitepress_settings['urls']['show_on_root'] = 'page';
		$sitepress_settings['urls']['root_page'] = 17;
		$sitepress_settings['urls']['directory_for_default_language'] = true;
		$sitepress->set_term_filters_and_hooks();
		$this->apply_filters_before_sitemap_generation();
		$filtered = has_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
		$this->assertEquals( 10, $filtered );
	}

	/**
	 * We use the filter hook 'wpseo_enable_xml_sitemap_transient_caching'
	 * as an action hook to alter setup before sitemap building
	 */
	private function apply_filters_before_sitemap_generation() {
		apply_filters( 'wpseo_build_sitemap_post_type', true );
	}

	public function test_get_home_url_filter() {
		$available_langs = array( 'de' => 'http://de.example.com/', 'fr' => 'http://fr.example.com/' );
		foreach ( $available_langs as $lang => $url ) {
			global $sitepress, $wpml_url_converter;
			$sitepress->switch_lang( $lang );
			$this->switch_to_langs_in_domains();

			$wpeo = new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress, $wpml_url_converter );
			$this->assertEquals( 10, has_filter( 'wpml_get_home_url', array( $wpeo, 'get_home_url_filter' ) ) );
			foreach ( array( '', 'test', 'test?a=b' ) as $arg ) {
				$home_url = trailingslashit( home_url( $arg ) );
				$expected = trailingslashit( $url . $arg );
				$this->assertEquals( $expected, $home_url );
			}
		}
	}

	public function test_add_languages_to_sitemap() {
		global $wpml_url_converter;

		// Mock SitePress object.
		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_default_language' )->willReturn( 'en' );
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array( 'en' => true, 'de' => true, 'ru' => true ) );

		// Languages in dirs option.
		$this->switch_to_langs_in_dirs();
		$stub = new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress_mock, $wpml_url_converter );
		$this->assertEquals( 10, has_filter( 'wpseo_sitemap_page_content', array( $stub, 'add_languages_to_sitemap' ) ) );
		$prepared = '';
		foreach ( array( 'http://example.org/de', 'http://example.org/ru' ) as $url ) {
			$prepared .= $this->mimic_sitemap_url_filter( $url );
		}

		$this->assertEquals( trim( $prepared ), trim( $stub->add_languages_to_sitemap() ) );

		// Languages in params.
		$this->switch_to_langs_as_params();
		$stub = new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress_mock, $wpml_url_converter );
		$this->assertEquals( 10, has_filter( 'wpseo_sitemap_page_content', array( $stub, 'add_languages_to_sitemap' ) ) );
		$prepared = '';
		foreach ( array( 'http://example.org?lang=de', 'http://example.org?lang=ru' ) as $url ) {
			$prepared .= $this->mimic_sitemap_url_filter( $url );
		}
		$this->assertEquals( trim( $prepared ), trim( $stub->add_languages_to_sitemap() ) );

		// Languages in domains.
		$this->switch_to_langs_in_domains();
		$sitepress_mock->method( 'get_setting' )->willReturnMap( array(
			array( 'language_negotiation_type', false, 2 ),
		) );
		$stub = new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress_mock, $wpml_url_converter );
		$this->assertEquals( false, has_filter( 'wpseo_sitemap_page_content', array( $stub, 'add_languages_to_sitemap' ) ) );
	}

	public function test_sitemap_url_filter() {
		global $sitepress, $wpml_url_converter;

		// Languages in dirs option.
		$stub = new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress, $wpml_url_converter );
		foreach (
			array(
				'http://example.org/de',
				'http://example.org?lang=de',
				'http://de.example.org',
			) as $url
		) {
			$this->assertEquals( trim( $this->mimic_sitemap_url_filter( $url ) ), trim( $stub->sitemap_url_filter( $url ) ) );
		}
	}

	private function mimic_sitemap_url_filter( $url ) {
		$url = htmlspecialchars( $url );

		$output = "\t<url>\n";
		$output .= "\t\t<loc>" . $url . "</loc>\n";
		$output .= '';
		$output .= "\t\t<changefreq>daily</changefreq>\n";
		$output .= "\t\t<priority>1.0</priority>\n";
		$output .= "\t</url>\n";

		return $output;
	}

	public function test_wpml_plugins_integration_setup() {
		global $wpml_query_filter;
		define( 'WPSEO_VERSION', '1.0.3' );
		$this->switch_to_langs_as_params();
		wpml_plugins_integration_setup();

		$this->assertEquals( false, has_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ) ) );
		$this->assertEquals( false, has_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ) ) );
		$this->assertEquals( false, has_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ) ) );
		$this->assertEquals( false, has_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ) ) );

		$this->switch_to_langs_in_dirs();
		wpml_plugins_integration_setup();

		$this->assertEquals( false, has_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ) ) );
		$this->assertEquals( false, has_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ) ) );
		$this->assertEquals( false, has_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ) ) );
		$this->assertEquals( false, has_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ) ) );

		$this->switch_to_langs_in_domains();
		wpml_plugins_integration_setup();

		$this->assertEquals( 10, has_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ) ) );
		$this->assertEquals( 10, has_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ) ) );
		$this->assertEquals( 10, has_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ) ) );
		$this->assertEquals( 10, has_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ) ) );
	}
}