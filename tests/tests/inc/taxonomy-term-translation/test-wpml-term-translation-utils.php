<?php

class Test_WPML_Term_Translation_Utils extends WPML_UnitTestCase {

	function test_sync_terms() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations, $sitepress;

		$sitepress->set_term_filters_and_hooks();

		$def_lang = icl_get_setting( 'default_language' );
		$sitepress->switch_lang( $def_lang );

		$post_original = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );
		$cat_parent    = wp_insert_term( 'parent' . rand_str(), 'category' );

		$cat_child = wp_insert_term(
			'child' . rand_str(),
			'category',
			array( 'parent' => $cat_parent[ 'term_id' ] )
		);
		$this->assertTrue( is_array( $cat_child ),
						   serialize( $cat_child ) . " is not a term, there was an error creating the child term." );
		$cat_grandchild   = wp_insert_term(
			'grandchild' . rand_str(),
			'category',
			array( 'parent' => $cat_child[ 'term_id' ] )
		);
		$original_cat_ids = array(
			$cat_grandchild[ 'term_id' ],
			$cat_child[ 'term_id' ],
			$cat_parent[ 'term_id' ]
		);
		wp_set_post_categories(
			$post_original,
			$original_cat_ids
		);
		$second_lang                  = 'fr';
		$translated_post              = $sitepress->make_duplicate( $post_original, $second_lang );
		$translated_post_cat_term_ids = wp_get_object_terms(
			$translated_post,
			'category',
			array( 'fields' => 'ids' )
		);

		$wpml_term_translations->reload();

		$this->assertCount(
			count( array_unique( $original_cat_ids ) ),
			array_unique( $translated_post_cat_term_ids )
		);

		global $wpml_post_translations;

		$post_no_dupl = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );
		wp_set_post_categories(
			$post_no_dupl,
			$original_cat_ids
		);

		$trid             = $wpml_post_translations->get_element_trid( $post_no_dupl );
		$post_no_dupl_tra = wpml_test_insert_post( $second_lang, 'post', $trid, rand_str() );
		wp_set_object_terms( $post_no_dupl_tra, array(), 'category' );
		$translated_post_cats = wp_get_object_terms( $post_no_dupl_tra, 'category' );
		$this->assertEmpty( $translated_post_cats );

		$term_helper = wpml_get_term_translation_util();
		$term_helper->sync_terms( $post_no_dupl, $second_lang );
		$translated_post_cats_term_ids = wp_get_object_terms(
			$post_no_dupl_tra,
			'category',
			array( 'fields' => 'ids' )
		);

		$this->assertCount(
			count( array_unique( $original_cat_ids ) ),
			array_unique( $translated_post_cats_term_ids )
		);
	}

}
