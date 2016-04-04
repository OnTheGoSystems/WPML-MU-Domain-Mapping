<?php

class Test_Slug_Filter extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2524
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2702
	 */
	function test_wp_unique_post_slug() {
		global $wpml_post_translations;
		$wpdb              = $this->get_wpdb_mock();
		$sitepress         = $this->get_sitepress_mock();
		$post_translations = $this->get_post_translation_mock();
		$sitepress->method( 'is_translated_post_type')->willReturn( true );
		$sitepress->method( 'get_current_language' )->willReturn( 'fr' );
		$sitepress->method( 'post_translations' )->willReturn( $wpml_post_translations );

		// Set mockups return values in "find_unique_slug_post" method
		$wpdb->method( 'prepare' )->will( $this->returnCallback(
			function( $post_name_check_sql = null, $slug = null, $post_id = null, $post_language = null, $post_type = null ) {
				$already_suffixed = substr( $slug, -2, 2 ) === '-2' ? true : false;
				if ( (bool) $post_language && !$already_suffixed ) {
					return true;
				} else {
					return false;
				}
			}
		)
		);
		$wpdb->method( 'get_var' )->will( $this->returnCallback(
			function( $has_lang ) {
				return $has_lang;
			}
		)
		);
		$wpdb->method( 'get_results' )->willReturn( array() );

		// Tested values
		$slug_suggested = 'my-unique-slug';
		$post_id        = 53;
		$post_status    = 'draft';
		$post_type      = 'page';
		$post_parent    = false;
		$slug           = 'my-unique-slug';

		$subject       = new WPML_Slug_Filter( $wpdb, $sitepress, $post_translations );

		remove_all_filters( 'wpml_save_post_lang' );
		add_filter( 'wpml_save_post_lang', function( $language ) {
			return null;
		});

		$returned_slug = $subject->wp_unique_post_slug( $slug_suggested, $post_id, $post_status, $post_type, $post_parent, $slug );
		$this->assertEquals( $slug_suggested, $returned_slug );

		remove_all_filters( 'wpml_save_post_lang' );
		add_filter( 'wpml_save_post_lang', function( $language ) {
			return 'fr';
		});

		$returned_slug = $subject->wp_unique_post_slug( $slug_suggested, $post_id, $post_status, $post_type, $post_parent, $slug );
		$this->assertNotEquals( $slug_suggested, $returned_slug );

		remove_all_filters( 'wpml_save_post_lang' );
	}

}