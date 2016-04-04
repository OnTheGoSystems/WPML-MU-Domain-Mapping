<?php

class Test_WPML_Post_Hierarchy extends WPML_UnitTestCase {

	function test_make_duplicate() {
		global $wpml_post_translations, $sitepress_settings;

		$post_a = wpml_test_insert_post( 'en', 'page', false, rand_str(), "0" );
		$post_b = wpml_test_insert_post( 'en', 'page', false, rand_str(), $post_a );
		$post_c = wpml_test_insert_post( 'en', 'page', false, rand_str(), $post_b );

		$trid_a = $wpml_post_translations->get_element_trid( $post_a );
		$trid_b = $wpml_post_translations->get_element_trid( $post_b );
		$trid_c = $wpml_post_translations->get_element_trid( $post_c );

		$translation_a = wpml_test_insert_post( 'de', 'page', $trid_a, rand_str(), "0" );
		$translation_b = wpml_test_insert_post( 'de', 'page', $trid_b, rand_str(), $translation_a );
		$translation_c = wpml_test_insert_post( 'de', 'page', $trid_c, rand_str(), $translation_b );

		$this->assertEquals( $trid_a, $wpml_post_translations->get_element_trid( $post_a ) );
		$this->assertEquals( $trid_b, $wpml_post_translations->get_element_trid( $post_b ) );
		$this->assertEquals( $trid_c, $wpml_post_translations->get_element_trid( $post_c ) );

		$this->assertEquals( $trid_a, $wpml_post_translations->get_element_trid( $translation_a ) );
		$this->assertEquals( $trid_b, $wpml_post_translations->get_element_trid( $translation_b ) );
		$this->assertEquals( $trid_c, $wpml_post_translations->get_element_trid( $translation_c ) );

		$this->assertEquals( 'en', $wpml_post_translations->get_element_lang_code( $post_a ) );
		$this->assertEquals( 'en', $wpml_post_translations->get_element_lang_code( $post_b ) );
		$this->assertEquals( 'en', $wpml_post_translations->get_element_lang_code( $post_c ) );

		$this->assertEquals( 'de', $wpml_post_translations->get_element_lang_code( $translation_a ) );
		$this->assertEquals( 'de', $wpml_post_translations->get_element_lang_code( $translation_b ) );
		$this->assertEquals( 'de', $wpml_post_translations->get_element_lang_code( $translation_c ) );

		$this->assertEquals( $post_a, wp_get_post_parent_id( $post_b ) );
		$this->assertEquals( $post_b, wp_get_post_parent_id( $post_c ) );

		$this->assertEquals( $translation_a, wp_get_post_parent_id( $translation_b ) );
		$this->assertEquals( $translation_b, wp_get_post_parent_id( $translation_c ) );

		$trans_a_arr = get_post( $translation_a, ARRAY_A );
		$trans_b_arr = get_post( $translation_b, ARRAY_A );
		$trans_c_arr = get_post( $translation_c, ARRAY_A );

		$trans_a_arr[ 'post_parent' ] = $translation_c;
		$trans_b_arr[ 'post_parent' ] = $translation_a;
		$trans_c_arr[ 'post_parent' ] = "0";

		wp_update_post( $trans_c_arr );
		wp_update_post( $trans_b_arr );
		wp_update_post( $trans_a_arr );

		clean_post_cache( $translation_a );
		clean_post_cache( $translation_b );
		clean_post_cache( $translation_c );

		$this->assertEquals( $translation_c, wp_get_post_parent_id( $translation_a ) );
		$this->assertEquals( $translation_a, wp_get_post_parent_id( $translation_b ) );
		$this->assertFalse( (bool) wp_get_post_parent_id( $translation_c ) );
		$this->assertEquals( 'de', $wpml_post_translations->get_element_lang_code( $translation_a ) );
		$this->assertEquals( 'de', $wpml_post_translations->get_element_lang_code( $translation_b ) );
		$this->assertEquals( 'de', $wpml_post_translations->get_element_lang_code( $translation_c ) );
		$this->assertEquals( 'en', $wpml_post_translations->get_source_lang_code( $translation_a ) );
		$this->assertEquals( 'en', $wpml_post_translations->get_source_lang_code( $translation_b ) );
		$this->assertEquals( 'en', $wpml_post_translations->get_source_lang_code( $translation_c ) );
		$this->assertEquals( $post_a, $wpml_post_translations->get_original_element( $translation_a ) );
		$this->assertEquals( $post_b, $wpml_post_translations->get_original_element( $translation_b ) );
		$this->assertEquals( $post_c, $wpml_post_translations->get_original_element( $translation_c ) );

		icl_set_setting( 'sync_page_parent', 1, true );
		$sitepress_settings[ 'sync_page_parent' ] = 1;
		$post_actions                             = wpml_test_get_admin_post_action( $sitepress_settings );

		$sync_helper = wpml_get_hierarchy_sync_helper();

		$unsynched_data = $sync_helper->get_unsynced_elements( 'page' );

		wpml_load_request_handler( true, $sitepress_settings[ 'active_languages' ], 'en' );

		$post_actions->init();

		$orig_a_arr = get_post( $post_a, ARRAY_A );
		$orig_b_arr = get_post( $post_b, ARRAY_A );
		$orig_c_arr = get_post( $post_c, ARRAY_A );

		$orig_a_arr[ 'post_title' ] .= 'orig';
		$orig_b_arr[ 'post_title' ] .= 'orig';
		$orig_c_arr[ 'post_title' ] .= 'orig';

		wp_update_post( $orig_a_arr );
		wp_update_post( $orig_b_arr );
		wp_update_post( $orig_c_arr );

		clean_post_cache( $post_a );
		clean_post_cache( $post_b );
		clean_post_cache( $post_c );

		$sync_helper->sync_element_hierarchy( 'page' );

		$unsynced_elements = $sync_helper->get_unsynced_elements( 'page' );
		$this->assertCount( 0, $unsynced_elements );

		clean_post_cache( $translation_a );
		clean_post_cache( $translation_b );
		clean_post_cache( $translation_c );

		$this->assertEquals( $translation_a, wp_get_post_parent_id( $translation_b ) );
		$this->assertEquals( $translation_b, wp_get_post_parent_id( $translation_c ) );
		$this->assertFalse( (bool) wp_get_post_parent_id( $translation_a ) );
		$this->assertCount( 2, $unsynched_data );

		$this->check_remove_parent( $orig_b_arr );
	}

	private function check_remove_parent( $post_arr ) {
		$post_arr[ 'post_parent' ] = '0';
		wp_update_post( $post_arr );
		clean_post_cache( $post_arr[ 'ID' ] );
		$this->assertFalse( (bool) wp_get_post_parent_id( $post_arr[ 'ID' ] ) );
	}

	public function test_just_parent_child() {
		global $wpml_post_translations;

		$post_parent = wpml_test_insert_post( 'en', 'page', false, rand_str(), "0" );
		$post_child  = wpml_test_insert_post( 'en', 'page', false, rand_str(), $post_parent );

		$tr_par   = wpml_test_insert_post(
			'fr',
			'page',
			$wpml_post_translations->get_element_trid( $post_parent ),
			rand_str(),
			"0"
		);
		$tr_child = wpml_test_insert_post(
			'fr',
			'page',
			$wpml_post_translations->get_element_trid( $post_child ),
			rand_str(),
			"0"
		);

		$sync_helper = wpml_get_hierarchy_sync_helper();

		$sync_helper->sync_element_hierarchy( 'page' );

		clean_post_cache( $tr_par );
		clean_post_cache( $tr_child );

		$this->assertEquals( $tr_par, wp_get_post_parent_id( $tr_child ) );
		$this->assertFalse( (bool) wp_get_post_parent_id( $tr_par ) );

		$post_parent = wpml_test_insert_post( 'en', 'page', false, rand_str(), "0" );
		$post_child  = wpml_test_insert_post( 'en', 'page', false, rand_str(), $post_parent );

		$unsynced_elements = $sync_helper->get_unsynced_elements( 'page' );
		$this->assertCount( 0, $unsynced_elements );

		$tr_par   = wpml_test_insert_post(
			'fr',
			'page',
			$wpml_post_translations->get_element_trid( $post_child ),
			rand_str(),
			"0"
		);
		$tr_child = wpml_test_insert_post(
			'fr',
			'page',
			$wpml_post_translations->get_element_trid( $post_parent ),
			rand_str(),
			"0"
		);

		$this->assertEquals( 'en', $wpml_post_translations->get_source_lang_code( $tr_par ) );
		$this->assertEquals( 'en', $wpml_post_translations->get_source_lang_code( $tr_child ) );
		$sync_helper = wpml_get_hierarchy_sync_helper();

		$this->assertCount( 1, $sync_helper->get_unsynced_elements( 'page' ) );

		$sync_helper->sync_element_hierarchy( 'page' );
		clean_post_cache( $tr_par );
		clean_post_cache( $tr_child );

		$this->assertCount( 0, $sync_helper->get_unsynced_elements( 'page' ) );
		$this->assertEquals( "0", wp_get_post_parent_id( $tr_child ) );
		$this->assertEquals( $tr_child, wp_get_post_parent_id( $tr_par ) );
	}
}