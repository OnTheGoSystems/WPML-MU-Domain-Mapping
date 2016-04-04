<?php

class Test_WPML_Post_Duplication extends WPML_UnitTestCase {

	/** @var WP_UnitTest_Factory_For_Comment $comment_factory */
	private $comment_factory;

	function setUp() {
		global $sitepress, $iclTranslationManagement;
		parent::setUp();
		$this->comment_factory = new WP_UnitTest_Factory_For_Comment();
		$sitepress->set_setting( 'sync_comments_on_duplicates', 1, true );
		$iclTranslationManagement->init();
	}

	function test_make_duplicate() {
		global $sitepress, $wpml_post_translations;

		$master_id = wpml_test_insert_post( 'en', 'post', false, rand_str() );

		$sitepress->make_duplicate( $master_id, 'de' );
		$translations = $wpml_post_translations->get_element_translations( $master_id );
		$this->assertArrayHasKey( 'en', $translations );
		$this->assertArrayHasKey( 'de', $translations );
		$this->assertCount( 2, $translations );

		$this->comment_factory->create_post_comments( $master_id, 5 );
		$counts_master = get_comment_count( $master_id );
		$counts_dupl   = get_comment_count( $translations[ 'de' ] );
		$this->assertEquals( 5, $counts_master[ 'total_comments' ] );
		$this->assertEquals(
			5,
			$counts_dupl[ 'total_comments' ],
			'The duplicate post does not have the correct number of comments assigned to it.'
		);
		$this->master_comment_dupl_delete();
	}

	/**
	 * This tests documents the behaviour that custom fields that are set to
	 * explicitly not be translated or copied, but set to 'Do Nothing' do not
	 * even get copied when duplicating a post.
	 *
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1565
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2060
	 */
	public function test_duplicate_with_custom_fields() {
		global $sitepress, $iclTranslationManagement;

		$setting_factory = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );
		$target_lang     = 'de';
		$source_lang     = 'en';
		foreach (
			array(
				WPML_IGNORE_CUSTOM_FIELD,
				WPML_COPY_CUSTOM_FIELD,
				WPML_TRANSLATE_CUSTOM_FIELD
			) as $cf_translation_setting
		) {
			$master_id  = wpml_test_insert_post( $source_lang, 'post', false, rand_str() );
			$meta_key   = rand_str( 10 );
			$meta_value = rand_str( 10 );
			$setting    = $setting_factory->post_meta_setting( $meta_key );
			if ( $cf_translation_setting === WPML_IGNORE_CUSTOM_FIELD ) {
				$setting->set_to_nothing();
			} elseif ( $cf_translation_setting === WPML_COPY_CUSTOM_FIELD ) {
				$setting->set_to_copy();
			} elseif ( $cf_translation_setting === WPML_TRANSLATE_CUSTOM_FIELD ) {
				$setting->set_to_translatable();
			}
			$iclTranslationManagement->settings['custom_fields_translation'][ $meta_key ] = $cf_translation_setting;
			add_post_meta( $master_id, $meta_key, $meta_value, true );
			$duplicate_id = $sitepress->make_duplicate( $master_id, $target_lang );
			icl_cache_clear();
			wp_cache_init();
			$this->assertEquals( $meta_value, get_post_meta( $master_id, $meta_key, true ) );
			$this->assertEquals(
				$cf_translation_setting > WPML_IGNORE_CUSTOM_FIELD ? $meta_value : '',
				get_post_meta( $duplicate_id, $meta_key, true ) );
		}
	}

	public function test_duplicate_status() {
		global $sitepress;

		$target_lang  = 'de';
		$source_lang  = 'en';
		$master_id    = wpml_test_insert_post( $source_lang, 'post', false, rand_str() );
		$duplicate_id = $sitepress->make_duplicate( $master_id, $target_lang );
		wp_save_post_revision( $master_id );
		$post_status_helper = wpml_get_post_status_helper();

		$this->assertEquals(
			ICL_TM_DUPLICATE,
			$post_status_helper->get_status( $duplicate_id )
		);

		$this->assertFalse(
			$post_status_helper->needs_update( $duplicate_id )
		);
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1466
	 */
	public function test_chained_duplication() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress, $wpml_term_translations;

		$target_lang         = 'de';
		$chained_target_lang = 'fr';
		$source_lang         = 'en';

		$original_cat = wp_insert_term( rand_str(), 'category' );
		$cat_ttid     = $original_cat[ 'term_taxonomy_id' ];
		$cat_term_id  = $original_cat[ 'term_id' ];
		$sitepress->set_element_language_details( $cat_ttid, 'tax_category', false, $source_lang );
		$master_id = wpml_test_insert_post( $source_lang, 'post', false, rand_str() );
		wp_set_object_terms( $master_id, array( $cat_term_id ), 'category' );
		$duplicate_id = $sitepress->make_duplicate( $master_id, $target_lang );
		$this->assertGreaterThan( 1, $duplicate_id );
		$terms_on_duplicate = wp_get_object_terms( $duplicate_id, 'category' );

		$this->assertCount( 1, $terms_on_duplicate );
		$duplicate_cat = array_pop( $terms_on_duplicate );
		$this->assertEquals(
			$cat_ttid,
			$wpml_term_translations->element_id_in( $duplicate_cat->term_taxonomy_id, $source_lang )
		);
		wpml_load_core_tm();
		global $iclTranslationManagement;

		$iclTranslationManagement->reset_duplicate_flag( $duplicate_id );

		$chained_duplicate_id = $sitepress->make_duplicate( $duplicate_id, $chained_target_lang );

		$terms_on_duplicate = wp_get_object_terms( $chained_duplicate_id, 'category' );
		$this->assertCount( 1, $terms_on_duplicate );
		$chained_duplicate_cat = array_pop( $terms_on_duplicate );
		$this->assertEquals(
			$chained_target_lang,
			$wpml_term_translations->get_element_lang_code( $chained_duplicate_cat->term_taxonomy_id )
		);
		$this->assertEquals(
			$duplicate_cat->term_taxonomy_id,
			$wpml_term_translations->element_id_in( $chained_duplicate_cat->term_taxonomy_id, $target_lang )
		);
	}

	private function master_comment_dupl_delete() {
		global $sitepress, $wpml_post_translations;

		$sitepress->switch_lang( 'en' );
		$this->assertTrue( (bool) $sitepress->get_setting( 'sync_comments_on_duplicates' ) );
		$master_post = wpml_test_insert_post( 'en', 'post', false, rand_str() );
		$comments    = $this->comment_factory->create_post_comments( $master_post, 5 );
		clean_comment_cache( $comments );
		$this->assertCount( 5, get_comments( array( 'post_id' => $master_post ) ) );
		$sitepress->make_duplicate( $master_post, 'de' );
		$sitepress->make_duplicate( $master_post, 'fr' );
		$sitepress->make_duplicate( $master_post, 'it' );
		$counts_master = get_comment_count( $master_post );
		$translations  = $wpml_post_translations->get_element_translations( $master_post );
		$counts_dupl   = get_comment_count( $translations[ 'de' ] );
		$this->assertEquals( 5, $counts_master[ 'total_comments' ] );
		$this->assertEquals(
			5,
			$counts_dupl[ 'total_comments' ],
			'The duplicate post does not have the correct number of comments assigned to it.'
		);

		$sitepress->switch_lang( 'en' );
		$comments_on_master = get_comments(
			array(
				'post_id' => $master_post
			)
		);

		$first_comment = array_pop( $comments_on_master );
		wp_delete_comment( $first_comment->comment_ID, true );
		$counts_master = get_comment_count( $master_post );
		$counts_dupl   = get_comment_count( $translations[ 'de' ] );
		$counts_dupl2  = get_comment_count( $translations[ 'fr' ] );

		$this->assertEquals( 4, $counts_master[ 'total_comments' ] );
		$this->assertEquals( 4, $counts_dupl2[ 'total_comments' ] );
		$this->assertEquals(
			4,
			$counts_dupl[ 'total_comments' ],
			'The duplicate post does not have the correct number of comments assigned to it.'
		);

		$this->comment_factory->create_post_comments( $translations[ 'de' ], 10 );

		$counts_master = get_comment_count( $master_post );
		$counts_dupl   = get_comment_count( $translations[ 'de' ] );

		$this->assertEquals( 14, $counts_master[ 'total_comments' ] );
		$this->assertEquals(
			14,
			$counts_dupl[ 'total_comments' ],
			'The duplicate post does not have the correct number of comments assigned to it.'
		);

		wp_update_post( array( 'ID' => $master_post, 'post_title' => rand_str() ) );

		$this->assertEquals( 14, $counts_master[ 'total_comments' ] );
		$this->assertEquals(
			14,
			$counts_dupl[ 'total_comments' ],
			'The duplicate post does not have the correct number of comments assigned to it.'
		);
	}

	/**
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-673
	 */
	function test_page_template_sync() {
		global $wpdb, $sitepress;

		$source_lang = 'en';
		$target_lang = 'fr';
		$master_id   = wpml_test_insert_post( $source_lang, 'page', false, rand_str(), "0" );
		$template    = rand_str( 10 );
		update_post_meta( $master_id, '_wp_page_template', $template );
		$duplicate_id = $sitepress->make_duplicate( $master_id, $target_lang );

		$this->assertEquals( $template,
							 $wpdb->get_var( $wpdb->prepare( "	SELECT meta_value
																FROM {$wpdb->postmeta}
																WHERE post_id = %d
																	AND meta_key = '_wp_page_template'
																LIMIT 1",
															 $duplicate_id ) ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1715
	 */
	function test_password_sync() {
		global $wpdb, $sitepress;

		$source_lang = 'en';
		$target_lang = 'fr';
		$master_id   = wpml_test_insert_post( $source_lang, 'page', false, rand_str(), "0" );
		$password    = rand_str(10);
		$wpdb->update( $wpdb->posts, array( 'post_password' => $password ), array( 'ID' => $master_id ) );
		clean_post_cache($master_id);
		$duplicate_id = $sitepress->make_duplicate( $master_id, $target_lang );
		foreach ( array( $master_id, $duplicate_id ) as $post_id ) {
			$this->assertEquals( $password,
								 $wpdb->get_var( $wpdb->prepare( "SELECT post_password FROM {$wpdb->posts} WHERE ID = %d LIMIT 1",
																 $post_id ) ) );
			$this->assertTrue( post_password_required( $post_id ) );
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1380
	 */
	function test_wpml_hierarchy_duplication() {
		/** @var WPML_Post_Translation $wpml_post_translations */
		global $sitepress, $wpml_post_translations;

		$parent_id     = wpml_test_insert_post( 'en', 'page', false, rand_str(), "0" );
		$child_id      = wpml_test_insert_post( 'en', 'page', false, rand_str(), $parent_id );
		$grandchild_id = wpml_test_insert_post( 'en', 'page', false, rand_str(), $child_id );

		$this->assertEquals( $child_id, wp_get_post_parent_id( $grandchild_id ) );
		$this->assertEquals( $parent_id, wp_get_post_parent_id( $child_id ) );

		$this->all_posts_in_lang( 'en', array( $parent_id, $child_id, $grandchild_id ) );

		icl_set_setting( 'sync_page_parent', 1, true );

		$this->assertTrue( (bool) $sitepress->get_setting( 'sync_page_parent' ) );
		global $wpml_language_resolution;
		$lang_codes = array_diff( $wpml_language_resolution->get_active_language_codes(), array( 'en' ) );
		foreach ( $lang_codes as $lang_code ) {
			$dupl_ids = array();
			foreach ( array( $grandchild_id, $child_id, $parent_id ) as $pid ) {
				$dupl_ids[ $pid ] = $sitepress->make_duplicate( $pid, $lang_code );
			}
			$this->all_posts_in_lang( $lang_code, $dupl_ids );

			$this->assertEquals( $dupl_ids[ $parent_id ], wp_get_post_parent_id( $dupl_ids[ $child_id ] ) );
			$this->assertEquals( $dupl_ids[ $child_id ], wp_get_post_parent_id( $dupl_ids[ $grandchild_id ] ) );
			foreach ( $dupl_ids as $id => $dup_id ) {
				$this->assertEquals(
						$wpml_post_translations->get_element_trid( $id ),
						$wpml_post_translations->get_element_trid( $dup_id )
				);
			}
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2176
	 */
	public function test_update_original_content() {
		global $sitepress, $wpdb, $wpml_post_translations;

		list( $source_lang, $target_lang ) = $this->get_source_and_target_languages( 1 );
		$title        = rand_str();
		$master_id    = wpml_test_insert_post( $source_lang, 'page', false, $title );
		$duplicate_id = $sitepress->make_duplicate( $master_id, $target_lang );
		$this->assertEquals( $title, get_post_field( 'post_title', $duplicate_id ) );
		$this->assertEquals( $title, get_post_field( 'post_title', $master_id ) );
		$updated_title = rand_str();
		$wpdb->update( $wpdb->posts, array( 'post_title' => $updated_title ), array( 'ID' => $master_id ) );
		wp_cache_init();
		$this->assertEquals( $title, get_post_field( 'post_title', $duplicate_id ) );
		$this->assertEquals( $updated_title, get_post_field( 'post_title', $master_id ) );
		add_action( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );
		do_action( 'save_post', $master_id, get_post( $master_id ) );
		wp_cache_init();
		$this->assertEquals( $updated_title, get_post_field( 'post_title', $duplicate_id ) );
		$this->assertEquals( $updated_title, get_post_field( 'post_title', $master_id ) );
		remove_action( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100 );
	}

	function tearDown() {
		global $sitepress;

		$sitepress->set_setting( 'sync_comments_on_duplicates', 0, true );
		remove_all_filters( 'comments_clauses' );
		parent::tearDown();
	}
}