<?php

require_once ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-hierarchy-duplication.class.php';

class Test_WPML_Term_Hierarchy_Duplication extends WPML_UnitTestCase {

	public function test_duplicates_require_sync() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress, $wpml_term_translations, $wpdb;

		$def_lang = $sitepress->get_default_language();
		$sitepress->switch_lang( $def_lang );
		$taxonomy = 'category';
		list( $parent, $child, $grand_child ) = $this->get_three_terms( $taxonomy, $def_lang );
		$trids = array(
			$wpml_term_translations->get_element_trid( $parent[ 'term_taxonomy_id' ] ),
			$wpml_term_translations->get_element_trid( $child[ 'term_taxonomy_id' ] ),
			$wpml_term_translations->get_element_trid( $grand_child[ 'term_taxonomy_id' ] )
		);

		$sec_lang = 'de';
		$this->get_three_terms(
			$taxonomy,
			$sec_lang,
			false,
			$trids
		);

		$first_master_post = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );
		wp_set_object_terms(
			$first_master_post,
			array(
				$parent[ 'term_id' ],
				$child[ 'term_id' ],
				$grand_child[ 'term_id' ]
			),
			$taxonomy
		);
		$hierarchy_duplication = new WPML_Term_Hierarchy_Duplication( $wpdb, $sitepress );
		$hierarchy_sync_helper = wpml_get_hierarchy_sync_helper( 'term' );
		$this->assertCount( 2, $hierarchy_sync_helper->get_unsynced_elements( $taxonomy, 'en' ) );
		$this->assertEmpty( $hierarchy_duplication->duplicates_require_sync( array( $first_master_post ) ) );

		$sitepress->make_duplicate( $first_master_post, $sec_lang );
		$this->assertEmpty( $hierarchy_duplication->duplicates_require_sync( array( $first_master_post ) ) );

		list( $parent_to_dupl, $child_to_dupl, $grand_child_to_dupl ) = $this->get_three_terms( $taxonomy, $def_lang );

		$master_post = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );
		wp_set_object_terms(
			$master_post,
			array(
				$parent_to_dupl[ 'term_id' ],
				$child_to_dupl[ 'term_id' ],
				$grand_child_to_dupl[ 'term_id' ]
			),
			$taxonomy
		);

		$this->assertEmpty( $hierarchy_duplication->duplicates_require_sync( array( $master_post ) ) );

		$sitepress->make_duplicate( $master_post, $sec_lang );

		$this->assertEquals(
			array( $taxonomy ),
			$hierarchy_duplication->duplicates_require_sync( array( $master_post ) )
		);

		$c_tax = rand_str( 10 );
		wpml_test_reg_custom_taxonomy( $c_tax, true, true );
		$this->assertTrue( is_taxonomy_translated( $c_tax ) );
		wp_cache_init();

		list( $parent_to_dupl, $child_to_dupl, $grand_child_to_dupl ) = $this->get_three_terms( $c_tax, $def_lang );

		wp_set_object_terms(
			$master_post,
			array(
				$parent_to_dupl[ 'term_id' ],
				$child_to_dupl[ 'term_id' ],
				$grand_child_to_dupl[ 'term_id' ]
			),
			$c_tax,
			true
		);

		$this->check_with_two_tax( $master_post, $taxonomy, $c_tax );
	}

	private function check_with_two_tax( $master_post, $taxonomy, $c_tax ) {
		global $wpml_post_translations, $sitepress, $wpdb;

		$def_lang   = $sitepress->get_default_language();
		$third_lang = 'fr';
		$sitepress->make_duplicate( $master_post, $third_lang );

		$hierarchy_sync_helper = wpml_get_hierarchy_sync_helper( 'term' );
		$synced_post           = $wpml_post_translations->element_id_in( $master_post, $third_lang );
		$both_tax              = array( $taxonomy, $c_tax );
		$post_taxonomies       = get_post_taxonomies( $master_post );
		$this->assertTrue( in_array( $both_tax[ 0 ], $post_taxonomies ) );
		$this->assertTrue( in_array( $both_tax[ 1 ], $post_taxonomies ) );

		$this->assertCount( 3, wp_get_post_terms( $master_post, $c_tax ) );
		$sitepress->switch_lang( $third_lang );
		$this->assertCount( 3, wp_get_post_terms( $synced_post, $c_tax ) );
		$sitepress->switch_lang( $def_lang );
		$this->assertCount( 2, $hierarchy_sync_helper->get_unsynced_elements( $c_tax, 'en' ) );
		$hierarchy_duplication = new WPML_Term_Hierarchy_Duplication( $wpdb, $sitepress );

		$this->assertEquals(
			array( $taxonomy, $c_tax ),
			$hierarchy_duplication->duplicates_require_sync( array( $master_post ) )
		);

		$hierarchy_sync_helper->sync_element_hierarchy( $c_tax, $def_lang );
		$this->assertEquals( array(), $hierarchy_duplication->duplicates_require_sync( array() ) );

		$this->assertEquals(
			array( $taxonomy ),
			$hierarchy_duplication->duplicates_require_sync( array( $master_post ) )
		);

		$hierarchy_sync_helper->sync_element_hierarchy( $taxonomy, $def_lang );

		$this->assertEmpty( $hierarchy_duplication->duplicates_require_sync( array( $master_post ) ) );
	}

	private function get_three_terms(
		$taxonomy,
		$lang_code,
		$descendants = true,
		$trids = array( false, false, false )
	) {

		$parent      = wpml_test_insert_term( $lang_code, $taxonomy, $trids[ 0 ], rand_str(), 0 );
		$child       = wpml_test_insert_term(
			$lang_code,
			$taxonomy,
			$trids[ 1 ],
			rand_str(),
			$descendants ? $parent[ 'term_id' ] : 0
		);
		$grand_child = wpml_test_insert_term(
			$lang_code,
			$taxonomy,
			$trids[ 2 ],
			rand_str(),
			$descendants ? $child[ 'term_id' ] : 0
		);

		return array( $parent, $child, $grand_child );
	}
}