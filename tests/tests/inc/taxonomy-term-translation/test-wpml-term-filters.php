<?php

class Test_WPML_Term_Filters extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1636
	 */
	function test_init() {
		$taxonomy = rand_str( 9 );
		$this->assertFalse( has_action( "edit_{$taxonomy}" ) );
		wpml_test_reg_custom_taxonomy( $taxonomy, true, true );
		$this->assertTrue( is_taxonomy_translated( $taxonomy ) );
		$this->assertTrue( has_action( "edit_{$taxonomy}" ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2125
	 */
	function test_get_tax_hier_array() {
		global $wpdb;

		$taxonomy  = 'category';
		$def_lang  = 'en';
		$term      = wpml_test_insert_term( $def_lang, $taxonomy, false, 0 );
		$hierarchy = array();
		for ( $i = 0; $i < 10; $i ++ ) {
			$child_term                       = wpml_test_insert_term( $def_lang, $taxonomy, false, false, $term[ 'term_id' ] );
			$hierarchy[ $term['term_id'] ] [] = $child_term['term_id'];
		}

		if ( ! get_post( $hierarchy[ $term['term_id'] ][2] ) ) {
			$post_id = wpml_test_insert_post( $def_lang );
			$wpdb->update( $wpdb->posts, array( 'ID' => $hierarchy[ $term['term_id'] ][2] ), array( 'ID' => $post_id ) );
			$wpdb->update( $wpdb->prefix . 'icl_translations', array( 'element_id' => $hierarchy[ $term['term_id'] ][2] ), array(
				'element_id'   => $post_id,
				'element_type' => 'post_post'
			) );
		}

		$sp_mock     = $this->get_sitepress_mock();
		$term_filter = new WPML_Term_Filters( $wpdb, $sp_mock );
		$hiera_res   = $term_filter->get_tax_hier_array( $taxonomy, $def_lang );
		$children    = $hiera_res[ $term['term_id'] ];
		$this->assertEquals( count( $children ), count( array_unique( $children ) ) );
		foreach ( $hierarchy[ $term['term_id'] ] as $child ) {
			$this->assertTrue( in_array( $child, $children ) );
		}
	}
}