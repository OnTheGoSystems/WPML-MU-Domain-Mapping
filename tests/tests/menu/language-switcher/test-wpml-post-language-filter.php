<?php

class Test_WPML_Post_Language_Filter extends WPML_UnitTestCase {

	/** @var  WPML_Post_Language_Filter $post_lang_filter */
	private $post_lang_filter;

	private $current_lang = 'fr';

	function setUp() {
		parent::setUp();
		global $sitepress, $wpdb;
		$sitepress->switch_lang( $this->current_lang );
		require_once ICL_PLUGIN_PATH . '/menu/post-menus/wpml-post-language-filter.class.php';
		$this->post_lang_filter = new WPML_Post_Language_Filter( $wpdb, $sitepress );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1663
	 */
	function test_post_language_filter() {
		global $wpml_language_resolution, $sitepress;;

		$custom_post_type = rand_str( 8 );
		wpml_test_reg_custom_post_type( $custom_post_type );
		$_GET[ 'post_type' ] = $custom_post_type;
		$this->assertFalse( $sitepress->is_translated_post_type( $custom_post_type ) );
		$this->assertEquals( '', $this->post_lang_filter->post_language_filter() );
		$settings_helper = wpml_load_settings_helper();
		$settings_helper->set_post_type_translatable( $custom_post_type );
		foreach ( array( 'post', 'page', $custom_post_type ) as $p_type ) {
			$_GET[ 'post_type' ] = $p_type;

			$all_count    = 0;
			$active_langs = $wpml_language_resolution->get_active_language_codes();
			foreach ( $active_langs as $lang_code ) {
				wpml_test_insert_post( $lang_code, $p_type );
				$all_count ++;
				if ( $lang_code === $this->current_lang ) {
					wpml_test_insert_post( $lang_code, $p_type );
					$all_count ++;
				}
			}
			$html_post_arr = $this->post_lang_filter->post_language_filter();
			$lang_links    = $html_post_arr[ 'language_links' ];

			$this->check_individual_lang_links( $active_langs, $lang_links, $p_type );

			$all = array_pop( $lang_links );
			$this->assertEquals( 'all', $all[ 'code' ] );
			$this->assertEquals( $all_count, $all[ 'count' ] );
		}
	}

	private function check_individual_lang_links( $active_langs, $lang_links, $p_type ) {
		foreach ( array_diff( $active_langs, array( $this->current_lang ) ) as $lang_code ) {
			$lang_found   = false;
			$type_correct = false;
			foreach ( $lang_links as $link ) {
				if ( $link[ 'code' ] === $lang_code ) {
					$lang_found = true;
					if ( $link[ 'type' ] === $p_type ) {
						$type_correct = true;
						$this->assertEquals( $lang_code === $this->current_lang ? 2 : 1, $link[ 'count' ] );
					}
					break;
				}
			}

			$this->assertTrue( $lang_found );
			$this->assertTrue( $type_correct );
		}
	}
}