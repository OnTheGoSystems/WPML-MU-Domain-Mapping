<?php

class Test_SitePress extends WPML_UnitTestCase {

	public function test_author_link() {
		global $sitepress;

		$this->switch_to_langs_as_params();
		$sitepress->switch_lang( 'de' );
		$this->assertEquals( 'http://example.com/?lang=de&author=1', $sitepress->author_link( 'http://example.com/?lang=de?author=1' ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlmedia-75
	 */
	public function test_maybe_set_this_lang() {
		global $sitepress, $pagenow;

		$pagenow           = '';
		$lang              = rand_str();
		$admin_bar         = rand_str();
		$_GET['lang']      = $lang;
		$_GET['admin_bar'] = $admin_bar;
		$sitepress->maybe_set_this_lang();
		$this->assertEquals( $lang, $_GET['lang'] );
		$this->assertEquals( $admin_bar, $_GET['admin_bar'] );
		$pagenow = 'upload.php';
		$sitepress->maybe_set_this_lang();
		$this->assertNull( $_GET['lang'] );
		$this->assertNull( $_GET['admin_bar'] );
	}

	public function test_switch_lang() {
		$subject      = new SitePress();
		$initial_lang = $subject->get_current_language();
		$subject->update_language_cookie( $initial_lang );
		$switched_lang = 'de';
		$subject->switch_lang( $switched_lang );
		$this->assertEquals( $switched_lang, $subject->get_current_language() );
		$subject->switch_lang();
		$this->assertEquals( $initial_lang, $subject->get_current_language() );
		$subject->switch_lang( $switched_lang, $switched_lang );
		$this->assertEquals( $switched_lang, $subject->get_current_language() );
		$this->assertEquals( $switched_lang, $subject->get_language_cookie() );
		$subject->switch_lang();
		$this->assertEquals( $initial_lang, $subject->get_current_language() );
		$this->assertEquals( $initial_lang, $subject->get_language_cookie() );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2591
	 */
	public function test_pre_get_posts_with_wp_ajax_link() {
		global $sitepress, $wp_query;
		$wp_query->query_vars['suppress_filters'] = true;
		$_POST['action']                          = 'wp-link-ajax';
		$wp_query                                 = $sitepress->pre_get_posts( $wp_query );
		$this->assertFalse( $wp_query->query_vars['suppress_filters'] );
	}

	public function test_filtered_get_pages() {
		global $sitepress, $wpml_post_translations;

		$def_lang = wpml_get_setting_filter( false, 'default_language' );
		$sec_lang = 'fr';

		$max_posts = 10;

		for ( $i = 0; $i < $max_posts; $i ++ ) {
			$orig = wpml_test_insert_post( $def_lang, 'page', false, rand_str() );
			$trid = $wpml_post_translations->get_element_trid( $orig );
			wpml_test_insert_post( $sec_lang, 'page', $trid, rand_str() );
		}

		for ( $i = 0; $i < $max_posts; $i ++ ) {
			wpml_test_insert_post( $sec_lang, 'page', false, rand_str() );
		}

		$pages = get_pages();

		$expected_unfiltered = $max_posts * 3;
		$this->assertCount( $expected_unfiltered, $pages );

		add_filter( 'get_pages', array( $sitepress, 'exclude_other_language_pages2' ) );

		$pages                         = get_pages();
		$expected_for_default_language = $max_posts;
		$this->assertCount( $expected_for_default_language, $pages );
		//Check caching
		$pages = get_pages();
		$this->assertCount( $expected_for_default_language, $pages );

		$this->check_arguments_as_ids_and_arrays( $pages, $expected_for_default_language );

		$sitepress->switch_lang( $sec_lang );

		$pages                           = get_pages();
		$expected_for_secondary_language = $max_posts * 2;
		$this->assertCount( $expected_for_secondary_language, $pages );
		//Check caching
		$pages = get_pages();
		$this->assertCount( $expected_for_secondary_language, $pages );
	}

	/**
	 * @param array $pages
	 * @param int   $expected
	 */
	private function check_arguments_as_ids_and_arrays( $pages, $expected ) {
		global $sitepress;

		$pages_as_ids             = array();
		$pages_as_arrays          = array();
		$pages_as_objects_with_ID = array();
		foreach ( $pages as $page ) {
			$pages_as_ids[]             = $page->ID;
			$pages_as_arrays[]          = object_to_array( $page );
			$obj                        = new stdClass();
			$obj->ID                    = $page->ID;
			$pages_as_objects_with_ID[] = $obj;
		}
		$pages = $sitepress->exclude_other_language_pages2( $pages_as_ids );
		$this->assertCount( $expected, $pages );
		$pages = $sitepress->exclude_other_language_pages2( $pages_as_arrays );
		$this->assertCount( $expected, $pages );
		$pages = $sitepress->exclude_other_language_pages2( $pages_as_objects_with_ID );
		$this->assertCount( $expected, $pages );
	}


	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2720
	 */
	public function test_get_search_form_filter() {
		global $sitepress;
		$base_form = '<form><input type="search" name="s"></form>';

		$this->switch_to_langs_as_params();
		$form = $sitepress->get_search_form_filter( $base_form );
		$dom = new DOMDocument();
		$dom->loadHTML( $form );
		$xpath      = new DOMXPath( $dom );
		$lang_input = $xpath->query('//input[@name="lang"]');
		$this->assertEquals( 1, $lang_input->length );

		$this->switch_to_langs_in_dirs();
		$form = $sitepress->get_search_form_filter( $base_form );
		$this->assertEquals( $base_form, $form );
	}
}