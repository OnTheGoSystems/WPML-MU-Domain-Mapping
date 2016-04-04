<?php

class Test_Comments extends WPML_UnitTestCase {

	private $source_lang = 'en';

	function setUp() {
		global $iclTranslationManagement, $sitepress;
		parent::setUp();
		$sitepress->set_setting( 'sync_comments_on_duplicates', 1, true );
		wpml_load_core_tm();
		$iclTranslationManagement->init();
		wpml_get_setup_instance()->set_active_languages( explode( ',', WPML_TEST_LANGUAGE_CODES ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1368
	 */
	function testCommentsPerPost() {
		global $sitepress, $wpml_language_resolution;

		$comment_factory = new WP_UnitTest_Factory_For_Comment();
		$new_post        = wpml_test_insert_post( $this->source_lang, 'post' );
		$this->assertCount( count( array_filter( explode( ',', WPML_TEST_LANGUAGE_CODES ) ) ),
							$sitepress->get_active_languages() );
		$this->assertCount( count( array_filter( explode( ',', WPML_TEST_LANGUAGE_CODES ) ) ),
							$wpml_language_resolution->get_active_language_codes() );
		$expected_count = 10;
		$comment_factory->create_post_comments( $new_post, $expected_count );
		$this->turn_on_comment_clause_filter();
		$sitepress->switch_lang( 'en' );
		$this->assertEquals( 'en', $sitepress->get_current_language(), 'Language not set correctly.' );
		$comment_counts = get_comment_count( $new_post );
		$this->assertEquals( $expected_count, $comment_counts[ 'total_comments' ] );
		$comments = get_comments( array( 'post_id' => $new_post ) );
		$this->assertEquals( $expected_count, count( $comments ) );
		$this->assertEquals( $expected_count, count( get_comments() ) );

		$sitepress->switch_lang( 'de' );
		$comment_ids = array();
		foreach ( $comments as $comment ) {
			$comment_ids[ ] = $comment->comment_ID;
		}
		clean_comment_cache( $comment_ids );
		$this->assertEquals( 'de', $sitepress->get_current_language(), 'Language not set correctly.' );
		$comments_from_sec = get_comments();
		$this->assertEquals( 0, count( $comments_from_sec ) );

		$this->check_filter_compatibility();

		$this->check_chinese( $new_post, $expected_count );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1703
	 *
	 * @param int $post_id
	 * @param int $expected_count
	 */
	private function check_chinese( $post_id, $expected_count ) {
		global $wpml_language_resolution, $sitepress;

		$active_langs        = $wpml_language_resolution->get_active_language_codes();
		$expected_lang_count = count( array_filter( explode( ',', WPML_TEST_LANGUAGE_CODES ) ) );
		$this->assertCount( $expected_lang_count, $sitepress->get_active_languages() );
		$this->assertCount( $expected_lang_count, $active_langs );
		$setup_helper     = wpml_get_setup_instance();
		$cn_codes         = array( 'zh-hant', 'zh-hans' );
		$new_active_langs = array_merge( $active_langs, $cn_codes, array( 'ru' ) );
		$setup_helper->set_active_languages( $new_active_langs );
		$this->assertTrue( (bool) $sitepress->get_setting( 'setup_complete' ) );
		$this->assertCount( count( array_unique( $new_active_langs ) ),
			$wpml_language_resolution->get_active_language_codes() );
		$this->check_comments_duplication( $post_id, 'ru', $expected_count );
		foreach ( $cn_codes as $code ) {
			$this->check_comments_duplication( $post_id, $code, $expected_count );
			$this->set_check_langs_for_comments_wrong( $post_id );
			$expected_count -= 1;
		}
		$setup_helper->set_active_languages( $active_langs );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1703
	 *
	 * @param $post_id
	 */
	private function set_check_langs_for_comments_wrong( $post_id ) {
		global $sitepress, $wpml_post_translations;

		$code         = $wpml_post_translations->get_element_lang_code( $post_id );
		$sitepress->switch_lang($code);
		$active_langs = $sitepress->get_active_languages();
		$other_code   = array_rand( array_diff_key( $active_langs, array( $code => 1 ) ), 1 );

		$this->assertTrue( $other_code !== $code && isset( $active_langs[ $other_code ] ) );
		$comments = get_comments( array( '$post_id' => $post_id ) );
		$ids      = array();
		foreach ( $comments as $comment ) {
			$sitepress->set_element_language_details( $comment->comment_ID, 'comment', false, $other_code );
			$ids[ ] = $comment->comment_ID;
		}
		$this->check_against_legacy_result( $ids, $post_id, $code );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1703
	 *
	 * @param int[] $ids
	 * @param int $post_id
	 * @param string $code
	 */
	private function check_against_legacy_result( $ids, $post_id, $code ) {
		global $sitepress, $wpdb;

		clean_comment_cache( $ids );
		$this->assertEmpty( get_comments( array( '$post_id' => $post_id ) ) );
		$this->switch_to_legacy_filter();
		clean_comment_cache( $ids );
		$this->assertEmpty( get_comments( array( '$post_id' => $post_id ) ) );
		$this->turn_on_comment_clause_filter();

		if ( (bool) $ids === true ) {
			$all_count  = count( $ids );
			$removed_id = array_pop( $ids );

			foreach ( $ids as $comment ) {
				$sitepress->set_element_language_details( $comment, 'comment', false, $code );
			}

			clean_comment_cache( $ids );
			$result_filtered_by_iclt = get_comments( array( '$post_id' => $post_id ) );
			$this->assertCount( $all_count - 1, $result_filtered_by_iclt );
			foreach ( $result_filtered_by_iclt as $comment_filtered ) {
				$this->assertTrue( in_array( $comment_filtered->comment_ID, $ids ) );
			}
			clean_comment_cache( $ids );

			$this->switch_to_legacy_filter();
			$result_filtered_by_iclt_legacy = get_comments( array( '$post_id' => $post_id ) );
			$this->assertCount( $all_count - 1, get_comments( array( '$post_id' => $post_id ) ) );
			foreach ( $result_filtered_by_iclt_legacy as $comment_filtered ) {
				$this->assertTrue( in_array( $comment_filtered->comment_ID, $ids ) );
			}
			$this->turn_on_comment_clause_filter();
			$comment_debug_helper = new WPML_Post_Comments( $wpdb );
			list( $orphan_ids, $count_orphans ) = $comment_debug_helper->get_orphan_comments( false, 10 );
			$this->assertEquals( 1, $count_orphans );
			$this->assertEquals( (int) $removed_id, (int) $orphan_ids[ 0 ] );

			$comment_debug_helper->delete_orphans( 10 );
			$this->assertEquals( 0, $comment_debug_helper->get_orphan_comments( true, 10 ) );
		}
	}

	private function check_comments_duplication( $post_id, $code, $expected_count ) {
		global $sitepress, $wpml_language_resolution;

		$dupl_id = $sitepress->make_duplicate( $post_id, $code );
		$this->assertTrue( $wpml_language_resolution->is_language_active( $code ),
						   $code . ' is not an active language even though it should be!' );
		$sitepress->switch_lang( $code );
		$this->assertEquals( $code, $sitepress->get_current_language() );
		$this->assertGreaterThan( 1, $dupl_id );
		$comment_counts          = get_comment_count( $dupl_id );
		$original_comment_counts = get_comment_count( $post_id );
		$this->assertEquals( $expected_count, $original_comment_counts[ 'total_comments' ] );
		$this->assertEquals( $expected_count, $comment_counts[ 'total_comments' ] );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1416
	 */
	private function check_filter_compatibility() {
		global $wpdb;
		$wpdb->show_errors( true );

		$this->assertEquals( "", $wpdb->last_error );
		get_comments(
			array(
				'number'      => 5,
				'offset'      => 0,
				'status'      => 'approve',
				'post_status' => 'publish'
			)
		);
		$this->assertEquals( "", $wpdb->last_error );

		add_filter( 'comments_clauses', array( $this, 'comment_clause_dummy_filter' ), 10, 1 );
		$comments = get_comments( array( 'post_id' => 1 ) );
		$this->assertEquals( 0, count( $comments ) );
		remove_filter( 'comments_clauses', array( $this, 'comment_clause_dummy_filter' ), 10 );
		$this->assertEquals( "", $wpdb->last_error );
	}

	public function comment_clause_dummy_filter( $clauses ) {
		global $wpdb;

		$clauses[ 'where' ] .= " AND {$wpdb->posts}.post_type NOT IN ('shop_order','shop_order_refund')  AND  {$wpdb->posts}.post_type <> 'shop_webhook' ";

		return $clauses;
	}

	function legacy_comment_clause_filter( $clauses, $obj ) {
		global $wpdb, $sitepress;

		if ( isset( $obj->query_vars[ 'post_id' ] ) ) {
			$post_id = $obj->query_vars[ 'post_id' ];
		} elseif ( isset( $obj->query_vars[ 'post_ID' ] ) ) {
			$post_id = $obj->query_vars[ 'post_ID' ];
		}
		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
			if ( ! $sitepress->is_translated_post_type( $post->post_type ) ) {
				return $clauses;
			}
		}

		$current_language = $sitepress->get_current_language();

		if ( $current_language != 'all' ) {
			$clauses[ 'join' ]
				.= " JOIN {$wpdb->prefix}icl_translations icltr1 ON
									icltr1.element_id = {$wpdb->comments}.comment_ID
									JOIN {$wpdb->prefix}icl_translations icltr2 ON
									icltr2.element_id = {$wpdb->comments}.comment_post_ID
									";
			$clauses[ 'where' ] .= $wpdb->prepare( "
                                        AND icltr1.element_type = 'comment'
								   AND icltr1.language_code = %s
								   AND icltr2.language_code = %s
								   AND icltr2.element_type LIKE 'post%%' ",
												   $current_language,
												   $current_language );
		}

		return $clauses;
	}

	/**
	 * Switches the comment filtering to the way it worked before WPML 3.2
	 */
	private function switch_to_legacy_filter() {
		remove_all_filters( 'comments_clauses' );
		add_filter( 'comments_clauses', array( $this, 'legacy_comment_clause_filter' ), 10, 2 );
	}

	/**
	 * Adds the \SitePress::comments_clauses() filter
	 */
	private function turn_on_comment_clause_filter() {
		global $sitepress;

		remove_all_filters( 'comments_clauses' );
		add_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2 );
		$this->assertTrue(
			(bool) has_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ) ),
			'The comment clause filter is not active!'
		);
	}

	function tearDown() {
		global $sitepress;

		$sitepress->set_setting( 'sync_comments_on_duplicates', 0, true );
		remove_all_filters( 'comments_clauses' );
		parent::tearDown();
	}
}
