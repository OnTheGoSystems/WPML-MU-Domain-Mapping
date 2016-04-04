<?php

class Test_Frontend_Redirection extends WPML_UnitTestCase {

	private $lang_by_dir_helper;
	private $lang_by_dir_root_helper;
	private $lang_by_param_helper;
	private $lang_by_domain_helper;

	public function test_get_redirect_helper() {
		global $wpml_url_converter, $sitepress_settings;

		icl_set_setting( 'language_negotiation_type', 1, true );
		$default_lang = 'en';
		icl_set_setting( 'default_language', $default_lang, true );

		$urls                                   = icl_get_setting( 'urls', array() );
		$urls['root_page']                      = 0;
		$urls['directory_for_default_language'] = 0;
		$urls['show_on_root']                   = '';
		icl_set_setting( 'urls', $urls, true );
		$wpml_setup = wpml_get_setup_instance();
		$wpml_setup->set_active_languages( array( 'fr', 'de', 'en' ) );

		$this->page_by_path_test();

		$urls                                   = icl_get_setting( 'urls', array() );
		$urls['root_page']                      = 1;
		$urls['directory_for_default_language'] = 1;
		$urls['show_on_root']                   = 'html_file';
		icl_set_setting( 'urls', $urls, true );
		$redir = _wpml_get_redirect_helper();
		$this->assertEquals( 'WPML_Rootpage_Redirect_By_Subdir', get_class( $redir ) );
		$this->lang_by_dir_root_helper = $redir;

		icl_set_setting( 'language_domains',
			array( 'de' => 'http://example.de' ), true );
		icl_set_setting( 'language_negotiation_type', 2, true );
		$wpml_url_converter = load_wpml_url_converter( $sitepress_settings, 2,
			$default_lang );
		$redir              = _wpml_get_redirect_helper();
		$this->assertEquals( 'WPML_Redirect_By_Domain', get_class( $redir ) );
		$this->lang_by_domain_helper = $redir;
	}

	/**
	 * @lang https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1964
	 */
	public function test_get_redirect_target() {
		$tax_sync_options = array();
		/** @var PHPUnit_Framework_MockObject_MockObject|WPML_Lang_Parameter_Converter $url_converter */
		$url_converter = $this->getMockBuilder( 'WPML_Lang_Parameter_Converter' )->disableOriginalConstructor()->getMock();
		$abs_home      = 'http://test.dev/subdir';
		$url_converter->method( 'get_abs_home' )->willReturn( $abs_home );
		/** @var PHPUnit_Framework_MockObject_MockObject|WPML_Language_Resolution $lang_res */
		$lang_res = $this->getMockBuilder( 'WPML_Language_Resolution' )->disableOriginalConstructor()->getMock();
		$lang_res->method( 'is_language_hidden' )->willReturn( true );
		/** @var PHPUnit_Framework_MockObject_MockObject|WPML_Frontend_Request $request_handler */
		$request_handler = $this->getMockBuilder( 'WPML_Frontend_Request' )->disableOriginalConstructor()->getMock();
		$request_handler->method( 'show_hidden' )->willReturn( false );
		$subject = new WPML_Redirect_By_Param( $tax_sync_options, $url_converter, $request_handler, $lang_res );

		$this->assertEquals( $abs_home, $subject->get_redirect_target() );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1750
	 */
	public function test_param_redirection() {
		global $wpml_url_converter, $sitepress_settings, $wpml_post_translations;

		$wpml_url_converter = load_wpml_url_converter( $sitepress_settings, 3,
			$sitepress_settings['default_language'] );

		icl_set_setting( 'language_negotiation_type', 3, true );
		/** @var WPML_Redirect_By_Param $redir */
		$redir = _wpml_get_redirect_helper();
		$this->assertEquals( 'WPML_Redirect_By_Param', get_class( $redir ) );
		$this->lang_by_param_helper = $redir;
		$org_post_id                = wpml_test_insert_post( 'fr' );
		$this->assertEquals( 'fr', $wpml_post_translations->get_element_lang_code( $org_post_id ) );
		$trans_post_id = wpml_test_insert_post( 'en',
		                                        get_post_type( $org_post_id ),
		                                        $wpml_post_translations->get_element_trid( $org_post_id )
		);
		/** @var WPML_Redirect_By_Param $redir_from_post */
		$redir_from_post        = _wpml_get_redirect_helper();
		$_SERVER['REQUEST_URI'] = '/?lang=fr&p=' . $trans_post_id;
		$this->assertEquals(
			$this->get_query_string( 'http://example.com/?lang=fr&p=' . $org_post_id ),
			$this->get_query_string( $redir_from_post->get_redirect_target() )
		);
		$redir_from_post        = _wpml_get_redirect_helper();
		$_SERVER['REQUEST_URI'] = '/subdir/?lang=en';
		$this->assertEquals( '/subdir/', $redir_from_post->get_redirect_target() );
		$_SERVER['REQUEST_URI'] = '/subdir/?lang=en&foo=bar';
		$redir_from_post        = _wpml_get_redirect_helper();
		$this->assertEquals( '/subdir/?foo=bar', $redir_from_post->get_redirect_target() );
	}

	private function page_by_path_test() {
		/** @var WP_Query $wp_query */
		global $wpml_url_converter, $wp_query, $sitepress;

		$parent_name = rand_str();
		add_action( 'parse_query', array( $sitepress, 'parse_query' ) );
		$wpml_url_converter = $this->switch_to_langs_in_dirs();
		$redir              = _wpml_get_redirect_helper();
		$this->assertEquals( 'WPML_Redirect_By_Subdir', get_class( $redir ) );
		$this->lang_by_dir_helper = $redir;

		$new_page = wpml_test_insert_post( 'fr', 'page', false, $parent_name );
		$this->assertEquals( $parent_name, get_post( $new_page )->post_name );

		$pages            = $wp_query->query( 'pagename=' . $parent_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $new_page, $page_id_from_url );
		$page             = get_page_by_path( '/' . $parent_name );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $new_page, $page_id_from_url );
		$sitepress->switch_lang( 'fr' );
		$pages            = $wp_query->query( 'pagename=fr/' . $parent_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;

		$original_parent = $page_id_from_url;

		$this->assertEquals( $new_page, $page_id_from_url );

		$pages            = $wp_query->query( 'pagename=de/' . $parent_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;

		$this->assertEquals( $new_page, $page_id_from_url );

		$page_id_en_expected = $sitepress->make_duplicate( $new_page, 'en' );
		$this->assertEquals( $parent_name, get_post( $page_id_en_expected )->post_name );
		$sitepress->switch_lang( 'en' );
		$wp_query         = new WP_Query();
		$pages            = $wp_query->query( 'pagename=en/' . $parent_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $page_id_en_expected, $page_id_from_url );
		$pages            = $wp_query->query( 'pagename=de/' . $parent_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $page_id_en_expected, $page_id_from_url );

		$this->check_hierarchical_resolve( $original_parent, $page_id_en_expected );

		return $wpml_url_converter;
	}

	private function check_hierarchical_resolve( $original_parent, $duplicate_parent ) {
		/** @var WP_Query $wp_query */
		global $wpml_post_translations, $sitepress, $wpdb, $wp_query;

		$sitepress->switch_lang( 'en' );
		$api_mock = $this->get_wp_api_mock();
		$sitepress->set_wp_api( $api_mock );
		$child_name = rand_str();
		icl_set_setting( 'sync_page_parent', 1, true );
		$child_post = wpml_test_insert_post( 'fr', 'page', false, $child_name );
		$this->assertEquals( 'fr', $wpml_post_translations->get_element_lang_code( $child_post ) );
		$wpdb->update( $wpdb->posts, array( 'post_parent' => $original_parent ), array( 'ID' => $child_post ) );
		clean_post_cache( $child_post );
		clean_post_cache( $original_parent );
		$this->assertEquals( $original_parent, wp_get_post_parent_id( $child_post ) );
		$parent_name = get_post( $original_parent )->post_name;
		$this->assertEquals( $child_name, get_post( $child_post )->post_name );

		$page_id_child_dupl = $sitepress->make_duplicate( $child_post, 'en' );
		$this->assertEquals( $child_name, get_post( $page_id_child_dupl )->post_name );
		$this->assertEquals( get_post( $duplicate_parent )->post_name, $parent_name );

		clean_post_cache( $page_id_child_dupl );
		$this->assertEquals( 'en', $wpml_post_translations->get_element_lang_code( $page_id_child_dupl ) );
		$this->assertEquals(
			$wpml_post_translations->element_id_in( $original_parent, 'en' ),
			wp_get_post_parent_id( $page_id_child_dupl )
		);
		$pages            = $wp_query->query( 'pagename=' . $parent_name . '/' . $child_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $page_id_child_dupl, $page_id_from_url );

		$pages            = $wp_query->query( 'pagename=de/' . $parent_name . '/' . $child_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $page_id_child_dupl, $page_id_from_url );

		$sitepress->switch_lang( 'fr' );

		$pages            = $wp_query->query( 'pagename=fr/' . $parent_name . '/' . $child_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $child_post, $page_id_from_url );

		$pages            = $wp_query->query( 'pagename=de/' . $parent_name . '/' . $child_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $child_post, $page_id_from_url );

		$sitepress->switch_lang( 'fr' );

		$another_parent_name = rand_str();

		$another_parent       = wpml_test_insert_post( 'fr', 'page', false, $another_parent_name );
		$child_same_slug      = wpml_test_insert_post( 'fr', 'page', false, $child_name, $another_parent );
		$parent_same_as_child = wpml_test_insert_post( 'fr', 'page', false, $child_name );

		$pages            = $wp_query->query( 'pagename=fr/' . $another_parent_name . '/' . $child_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $child_same_slug, $page_id_from_url );

		$pages            = $wp_query->query( 'pagename=fr/' . $parent_name . '/' . $child_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $child_post, $page_id_from_url );

		$pages            = $wp_query->query( 'pagename=fr/' . $child_name );
		$page             = array_pop( $pages );
		$page_id_from_url = isset( $page ) ? $page->ID : false;
		$this->assertEquals( $parent_same_as_child, $page_id_from_url );
		$sitepress->set_wp_api( null );
	}

	private function get_query_string( $url ) {
		$parts = explode( '/?', $url );

		return array_pop( $parts );
	}
}