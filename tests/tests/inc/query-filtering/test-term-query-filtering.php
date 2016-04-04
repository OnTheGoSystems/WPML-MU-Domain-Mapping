<?php

class Test_Term_Query_Filtering extends WPML_UnitTestCase {

	function setUp() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress;
		parent::setUp();
		$sitepress->set_term_filters_and_hooks();
	}

	/**
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1490
	 */
	function test_child_of_filtering() {
		global $sitepress, $wpdb;

		$tax_name = rand_str( 9 );
		wpml_test_reg_custom_taxonomy( $tax_name );
		$def_lang = $sitepress->get_default_language();

		$parent         = wpml_test_insert_term( $def_lang, $tax_name, false, rand_str(), 0 );
		$child          = wpml_test_insert_term( $def_lang, $tax_name, false, rand_str(), $parent[ 'term_id' ] );
		$grand_child    = wpml_test_insert_term( $def_lang, $tax_name, false, rand_str(), $child[ 'term_id' ] );
		$another_parent = wpml_test_insert_term( $def_lang, $tax_name, false, rand_str(), 0 );
		$another_child  = wpml_test_insert_term(
			$def_lang,
			$tax_name,
			false,
			rand_str(),
			$another_parent[ 'term_id' ]
		);
		$this->check_child_counts( $tax_name, $parent, $another_parent, $another_child, $grand_child );
		$wpdb->delete( $wpdb->prefix . 'icl_translations', array( 'element_type' => 'tax_' . $tax_name ) );
		wp_cache_init();
		icl_cache_clear();
		$this->check_child_counts( $tax_name, $parent, $another_parent, $another_child, $grand_child );

		$tax_name       = 'category';
		$parent         = wpml_test_insert_term( $def_lang, $tax_name, false, rand_str(), 0 );
		$child          = wpml_test_insert_term( $def_lang, $tax_name, false, rand_str(), $parent[ 'term_id' ] );
		$grand_child    = wpml_test_insert_term( $def_lang, $tax_name, false, rand_str(), $child[ 'term_id' ] );
		$another_parent = wpml_test_insert_term( $def_lang, $tax_name, false, rand_str(), 0 );
		$another_child  = wpml_test_insert_term(
			$def_lang,
			$tax_name,
			false,
			rand_str(),
			$another_parent[ 'term_id' ]
		);

		$this->check_child_counts( $tax_name, $parent, $another_parent, $another_child, $grand_child );
	}

	private function check_child_counts( $tax_name, $parent, $another_parent, $another_child, $grand_child ) {

		$children_rec       = get_terms(
			$tax_name,
			array( 'child_of' => $parent[ 'term_id' ], 'hide_empty' => false )
		);
		$other_children_rec = get_terms(
			$tax_name,
			array( 'child_of' => $another_parent[ 'term_id' ], 'hide_empty' => false )
		);
		$no_child_terms     = get_terms(
			$tax_name,
			array( 'child_of' => $another_child[ 'term_id' ], 'hide_empty' => false )
		);

		$no_grand_child_terms = get_terms(
			$tax_name,
			array( 'child_of' => $grand_child[ 'term_id' ], 'hide_empty' => false )
		);

		$this->assertCount( 2, $children_rec );
		$this->assertCount( 1, $other_children_rec );
		$this->assertEmpty( $no_child_terms );
		$this->assertEmpty( $no_grand_child_terms );

	}

}