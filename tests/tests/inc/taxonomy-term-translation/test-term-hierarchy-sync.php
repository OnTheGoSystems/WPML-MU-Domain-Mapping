<?php

class Test_Term_Hierarchy_Sync extends WPML_UnitTestCase {

	function setUp() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress;
		parent::setUp();
		$sitepress->set_term_filters_and_hooks();
	}

	function test_make_duplicate() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations, $sitepress;

		wp_cache_init();
		icl_cache_clear();
		$def_lang    = wpml_get_setting_filter(false, 'default_language' );
		$second_lang = 'fr';
		$third_lang  = 'de';

		$sitepress->switch_lang( $def_lang );
		$post_a     = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );
		$cat_parent = wp_insert_term( 'parent' . rand_str(), 'category' );
		$this->assertArrayHasKey( 'term_taxonomy_id', $cat_parent );
		$this->assertArrayHasKey( 'term_id', $cat_parent );
		$cat_parent_ttid = $cat_parent[ 'term_taxonomy_id' ];
		$cat_child       = wp_insert_term( 'child' . rand_str(),
										   'category',
										   array( 'parent' => $cat_parent[ 'term_id' ] ) );
		$this->assertArrayHasKey( 'term_taxonomy_id', $cat_child );
		$this->assertArrayHasKey( 'term_id', $cat_child );
		$cat_child_ttid = $cat_child[ 'term_taxonomy_id' ];
		$cat_grandchild = wp_insert_term(
			'grandchild' . rand_str(),
			'category',
			array( 'parent' => $cat_child[ 'term_id' ] )
		);
		$this->assertArrayHasKey( 'term_taxonomy_id', $cat_grandchild );
		$this->assertArrayHasKey( 'term_id', $cat_grandchild );
		$cat_grandchild_ttid = $cat_grandchild[ 'term_taxonomy_id' ];
		wp_set_post_categories(
			$post_a,
			array(
				$cat_grandchild[ 'term_id' ],
				$cat_child[ 'term_id' ],
				$cat_parent[ 'term_id' ]
			)
		);
		$this->check_all_in_lang( array( $cat_parent_ttid, $cat_child_ttid, $cat_grandchild_ttid ), $def_lang );
		$this->assertEquals( $cat_parent[ 'term_id' ],
							 get_term_field( 'parent', $cat_child[ 'term_id' ], 'category' ) );
		$this->assertEquals( $cat_child[ 'term_id' ],
							 get_term_field( 'parent', $cat_grandchild[ 'term_id' ], 'category' ) );

		$sitepress->switch_lang( $second_lang );

		$translated_post        = $sitepress->make_duplicate( $post_a, $second_lang );
		$parent_trans_ttid      = $wpml_term_translations->element_id_in( $cat_parent_ttid, $second_lang );
		$child_trans_ttid       = $wpml_term_translations->element_id_in( $cat_child_ttid, $second_lang );
		$grand_child_trans_ttid = $wpml_term_translations->element_id_in( $cat_grandchild_ttid, $second_lang );

		$this->check_ttids_no_parent( array( $parent_trans_ttid, $child_trans_ttid, $grand_child_trans_ttid ) );

		$hierarchy_helper = wpml_get_hierarchy_sync_helper( 'term' );
		$unsynced         = $hierarchy_helper->get_unsynced_elements( 'category' );
		$this->assertCount( 2, $unsynced );

		$hierarchy_helper->sync_element_hierarchy( 'category' );
		$unsynced = $hierarchy_helper->get_unsynced_elements( 'category' );
		$this->assertCount( 0, $unsynced );
		$this->check_ttids_parent_ttids(
			array( $child_trans_ttid => $grand_child_trans_ttid, $parent_trans_ttid => $child_trans_ttid )
		);

		$sitepress->switch_lang( $def_lang );

		$super_parent = wp_insert_term( 'super_parent' . rand_str(), 'category' );
		wp_update_term(
			$cat_parent[ 'term_id' ],
			'category',
			array( 'parent' => $super_parent[ 'term_id' ] )
		);

		$post_a_arr = get_post( $post_a, ARRAY_A );
		$post_a_arr[ 'post_title' ] .= 'updated';
		wp_update_post( $post_a_arr );
		$another_translation_maybe = $sitepress->make_duplicate( $post_a, $second_lang );
		$this->assertEquals( $translated_post, $another_translation_maybe );

		$sitepress->switch_lang( $second_lang );

		$this->check_ttids_no_parent( $parent_trans_ttid );
		$sitepress->switch_lang( $third_lang );

		$term_third_deep = wpml_test_insert_term( $third_lang, 'category' );
		$third_lang_post = wpml_test_insert_post( $third_lang, 'post', false, rand_str() );

		wp_set_object_terms( $third_lang_post, array( $term_third_deep[ 'term_id' ] ), 'category' );
		$sitepress->make_duplicate( $third_lang_post, $def_lang );
		$sitepress->make_duplicate( $third_lang_post, $second_lang );

		$term_deepest_def = $wpml_term_translations->element_id_in( $term_third_deep[ 'term_taxonomy_id' ], $def_lang );
		$this->assertNotEquals( $term_deepest_def, $term_third_deep[ 'term_taxonomy_id' ] );
		$this->check_all_in_lang( array( $term_deepest_def ), $def_lang );

		$sitepress->switch_lang( $def_lang );
		$this->assertEquals( $cat_parent[ 'term_id' ],
							 get_term_field( 'parent', $cat_child[ 'term_id' ], 'category' ) );
		wp_update_term( $cat_child[ 'term_id' ], 'category', array( 'parent' => 0 ) );
		$this->assertNotEquals( $cat_parent_ttid, get_term_field( 'parent', $cat_child_ttid, 'category' ) );
		$this->assertEquals( 0, (int) get_term_field( 'parent', (int) $cat_child[ 'term_id' ], 'category' ) );
	}

	function test_sync_by_ref_lang() {

		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations, $sitepress;

		$def_lang    = $sitepress->get_setting( 'default_language' );
		$second_lang = 'fr';

		$sitepress->switch_lang( $def_lang );
		$post_a     = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );
		$cat_parent = wp_insert_term( 'parent' . rand_str(), 'category' );
		$this->assertArrayHasKey( 'term_taxonomy_id', $cat_parent );
		$this->assertArrayHasKey( 'term_id', $cat_parent );
		$cat_parent_ttid = $cat_parent[ 'term_taxonomy_id' ];
		$cat_child       = wp_insert_term( 'child' . rand_str(),
										   'category',
										   array( 'parent' => $cat_parent[ 'term_id' ] ) );
		$this->assertArrayHasKey( 'term_taxonomy_id', $cat_child );
		$this->assertArrayHasKey( 'term_id', $cat_child );
		$cat_child_ttid = $cat_child[ 'term_taxonomy_id' ];
		$cat_grandchild = wp_insert_term(
			'grandchild' . rand_str(),
			'category',
			array( 'parent' => $cat_child[ 'term_id' ] )
		);
		$this->assertArrayHasKey( 'term_taxonomy_id', $cat_grandchild );
		$this->assertArrayHasKey( 'term_id', $cat_grandchild );
		$cat_grandchild_ttid = $cat_grandchild[ 'term_taxonomy_id' ];

		wp_set_post_categories(
			$post_a,
			array(
				$cat_grandchild[ 'term_id' ],
				$cat_child[ 'term_id' ],
				$cat_parent[ 'term_id' ]
			)
		);

		$this->check_all_in_lang( array( $cat_parent_ttid, $cat_child_ttid, $cat_grandchild_ttid ), $def_lang );
		$this->check_ttids_parent_ttids(
			array( $cat_parent_ttid => $cat_child_ttid, $cat_child_ttid => $cat_grandchild_ttid )
		);
		$sitepress->switch_lang( $second_lang );

		$sitepress->make_duplicate( $post_a, $second_lang );
		$parent_trans_ttid      = $wpml_term_translations->element_id_in( $cat_parent_ttid, $second_lang );
		$child_trans_ttid       = $wpml_term_translations->element_id_in( $cat_child_ttid, $second_lang );
		$grand_child_trans_ttid = $wpml_term_translations->element_id_in( $cat_grandchild_ttid, $second_lang );

		$hierarchy_helper = wpml_get_hierarchy_sync_helper( 'term' );
		$unsynced         = $hierarchy_helper->get_unsynced_elements( 'category', $second_lang );
		$this->assertCount( 2, $unsynced );

		$hierarchy_helper->sync_element_hierarchy( 'category', $second_lang );
		$unsynced = $hierarchy_helper->get_unsynced_elements( 'category' );
		$this->assertCount( 0, $unsynced );

		$this->check_ttids_no_parent( array( $child_trans_ttid, $parent_trans_ttid, $grand_child_trans_ttid ) );

		$sitepress->switch_lang( $def_lang );

		$this->check_ttids_no_parent( array( $cat_parent_ttid, $cat_child_ttid, $cat_grandchild_ttid ) );

		$sitepress->switch_lang( $second_lang );

		$cat_grand_child_trans = get_term_by( 'term_taxonomy_id', $grand_child_trans_ttid, 'category' );
		$cat_parent_trans      = get_term_by( 'term_taxonomy_id', $parent_trans_ttid, 'category' );

		wp_update_term(
			$cat_grand_child_trans->term_id,
			'category',
			array( 'parent' => $cat_parent_trans->term_id )
		);

		$this->check_ttids_no_parent( array( $cat_parent_ttid, $cat_child_ttid, $cat_grandchild_ttid ) );

		$this->assertEquals(
			$wpml_term_translations->get_element_trid( $cat_parent_ttid ),
			$wpml_term_translations->get_element_trid( $cat_parent_trans->term_taxonomy_id )
		);

		$this->assertEquals(
			$wpml_term_translations->get_element_trid( $cat_grandchild_ttid ),
			$wpml_term_translations->get_element_trid( $cat_grand_child_trans->term_taxonomy_id )
		);

		$this->check_sync_zero_count( $second_lang, 1, 'category' );

		$sitepress->switch_lang( $def_lang );
		$this->check_ttids_parent_ttids(
			array( $cat_parent_ttid => $cat_grandchild_ttid )
		);
	}

	private function check_sync_zero_count( $lang, $initial, $taxonomy ) {
		$hierarchy_helper = wpml_get_hierarchy_sync_helper( 'term' );

		$unsynced = $hierarchy_helper->get_unsynced_elements( $taxonomy, $lang );
		$this->assertCount( $initial, $unsynced );
		$hierarchy_helper->sync_element_hierarchy( $taxonomy, $lang );
		$unsynced = $hierarchy_helper->get_unsynced_elements( $taxonomy, $lang );
		$this->assertCount( 0, $unsynced );
	}

	private function check_ttids_parent_ttids( $ttids_with_parents ) {
		global $wpdb;

		foreach ( $ttids_with_parents as $parent => $ttid ) {
			$this->assertEquals( $parent,
								 $wpdb->get_var( "SELECT ttp.term_taxonomy_id
													 FROM {$wpdb->term_taxonomy} tt
													 JOIN {$wpdb->term_taxonomy} ttp
													  ON ttp.term_id = tt.parent
													 WHERE tt.term_taxonomy_id = {$ttid}" ) );
		}
	}

	private function check_ttids_no_parent( $ttids ) {
		global $wpdb;

		$ttids = (array) $ttids;

		foreach ( $ttids as $ttid ) {
			$this->assertEquals( 0,
								 $wpdb->get_var( "SELECT parent
													 FROM {$wpdb->term_taxonomy}
													 WHERE term_taxonomy_id = {$ttid}" ) );
		}
	}

	private function check_all_in_lang( $ttids, $lang ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations;

		foreach ( $ttids as $ttid ) {
			$this->assertEquals( $lang, $wpml_term_translations->get_element_lang_code( $ttid ) );
		}
	}
}