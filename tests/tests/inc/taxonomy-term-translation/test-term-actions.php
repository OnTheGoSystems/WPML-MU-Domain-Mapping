<?php

class Test_WPML_Term_Actions extends WPML_UnitTestCase {

	/**
	 * @link  https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1938
	 */
	function test_added_term_relationships() {
		/** @var WPML_Post_Translation $wpml_post_translations */
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_post_translations, $wpml_term_translations, $sitepress, $wpdb;

		$original_post   = wpml_test_insert_post( 'en', 'post' );
		$trid_post       = $wpml_post_translations->get_element_trid( $original_post );
		$translated_post = wpml_test_insert_post( 'de', 'post', $trid_post );
		$wpdb->delete( $wpdb->term_relationships, array( 'object_id' => $translated_post ) );
		$term_original   = wpml_test_insert_term( 'en', 'category' );
		$trid_term       = $wpml_term_translations->get_element_trid( $term_original['term_taxonomy_id'] );
		$term_translated = wpml_test_insert_term( 'de', 'category', $trid_term );
		wp_set_object_terms( $original_post, array( (int) $term_original['term_id'] ), 'category' );
		$this->assertEmpty( $this->get_ttids_on_post( $translated_post ) );
		$subject = $sitepress->get_term_actions_helper();
		$subject->added_term_relationships( $original_post );
		$this->assertEquals( array( $term_translated['term_taxonomy_id'] ),
		                     $this->get_ttids_on_post( $translated_post ) );

		$this->check_not_called_on_self( $original_post );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1966
	 *
	 * @param int $original_post post with a few terms attached
	 */
	private function check_not_called_on_self( $original_post ) {
		global $wpdb;

		$post_translations = $this->get_post_translation_mock();
		$term_translations = $this->get_term_translation_mock();
		$wp_api_mock       = $this->get_wp_api_mock();
		$wp_api_mock->expects( $this->never() )->method( 'wp_set_object_terms' )->with( $this->anything() );
		$sitepress = $this->get_sitepress_mock( $wp_api_mock );
		$sitepress->method( 'is_translated_taxonomy' )->willReturn( true );
		$subject = new WPML_Term_Actions( $sitepress, $wpdb, $post_translations, $term_translations );
		$subject->added_term_relationships( $original_post );
	}

	private function get_ttids_on_post( $post_id ) {
		global $wpdb;

		$ttids = $wpdb->get_col( $wpdb->prepare( "  SELECT term_taxonomy_id
													FROM {$wpdb->term_relationships}
													WHERE object_id = %d ",
		                                         $post_id ) );
		foreach ( $ttids as &$ttid ) {
			$ttid = (int) $ttid;
		}

		return $ttids;
	}
}