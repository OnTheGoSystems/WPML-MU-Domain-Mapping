<?php

class Test_Pre_Option_Page extends WPML_UnitTestCase {

	public function test_front_and_posts_pages() {
		global $switched, $sitepress, $wpdb;

		$options = array(
			'page_on_front',
			'page_for_posts',
		);

		add_filter( 'pre_option_page_on_front', array( $sitepress, 'pre_option_page_on_front' ) );
		add_filter( 'pre_option_page_for_posts', array( $sitepress, 'pre_option_page_for_posts' ) );
		add_filter( 'trashed_post', array( $sitepress, 'fix_trashed_front_or_posts_page_settings' ) );

		foreach ( $options as $option ) {

			list( $original_id, $translation_id ) = $this->setup_original_and_translation();

			update_option( $option, $original_id );

			$pre_option_page = new WPML_Pre_Option_Page( $wpdb, $sitepress, $switched, 'en' );
			$this->assertEquals( $original_id, $pre_option_page->get( $option ) );

			$pre_option_page = new WPML_Pre_Option_Page( $wpdb, $sitepress, $switched, 'de' );
			$this->assertEquals( $translation_id, $pre_option_page->get( $option ) );

			$sitepress->switch_lang( 'de' );
			wp_trash_post( $translation_id );

			$sitepress->switch_lang( 'en' );
			wp_cache_init();
			$this->assertEquals( $original_id, get_option( $option ) );
		}
	}

	private function setup_original_and_translation( $sec_lang = 'de' ) {
		global $sitepress_settings, $wpml_post_translations, $wpdb;

		$def_lang                        = $sitepress_settings['default_language'];
		$wpml_post_translations          = new WPML_Admin_Post_Actions( $sitepress_settings, $wpdb );
		$orig                            = wpml_test_insert_post( $def_lang, 'page', false, rand_str() );
		$trid                            = $wpml_post_translations->get_element_trid( $orig );
		$trans                           = wpml_test_insert_post( $sec_lang,
			'page',
			$trid,
			rand_str() );

		return array( $orig, $trans );
	}
	
	function tearDown() {
		delete_option( 'page_for_posts' );
		delete_option( 'page_on_front' );
		parent::tearDown();
	}
	

}