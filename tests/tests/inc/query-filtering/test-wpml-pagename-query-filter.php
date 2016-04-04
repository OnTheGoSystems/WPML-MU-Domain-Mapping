<?php

class Test_WPML_Page_Name_Query_Filter extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1787
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1603
	 */
	function test_filter_page_name() {
		global $sitepress, $wpdb;

		$test_page_name = rand_str( 10 );

		$this->check_query_changes( $test_page_name );
		list( $source_lang, $target_lang ) = $this->get_source_and_target_languages( 1 );
		$sitepress->switch_lang( $source_lang );
		$original_page_id = wpml_test_insert_post( $source_lang, 'page', false, $test_page_name );
		$this->check_query_changes( $test_page_name, $original_page_id );
		$this->check_query_changes( $test_page_name, $original_page_id, true );
		$secondary_page_id = wpml_test_insert_post( $target_lang, 'page', false, $test_page_name );
		$wpdb->update( $wpdb->posts, array( 'post_name' => $test_page_name ), array( 'ID' => $secondary_page_id ) );
		$this->check_query_changes( $test_page_name, $original_page_id );
		$sitepress->switch_lang( $target_lang );
		$this->check_query_changes( $test_page_name, $secondary_page_id );
		$this->check_query_changes( $test_page_name, $secondary_page_id, true );

		$this->check_nested_equal_slugs( $source_lang, $target_lang );
		$this->check_par_child_same_slug();
		$this->check_three_nested_levels();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1933
	 */
	function test_page_for_posts() {
		/** @var SitePress $sitepress */
		global $wpml_post_translations, $wpdb, $sitepress;

		$sitepress->switch_lang();
		list( $source_lang, $target_lang ) = $this->get_source_and_target_languages( 1 );
		$test_page_name    = 'blog';
		$original_page_id  = wpml_test_insert_post( $source_lang, 'page', false, $test_page_name );
		$secondary_page_id = wpml_test_insert_post( $target_lang,
		                                            'page',
		                                            $wpml_post_translations->get_element_trid( $original_page_id ),
		                                            $test_page_name );
		$wpdb->update( $wpdb->posts, array( 'post_name' => $test_page_name ), array( 'ID' => $secondary_page_id ) );
		update_option( 'page_for_posts', $original_page_id );
		update_option( 'show_on_front', 'page' );
		add_filter( 'pre_option_page_for_posts', array( $sitepress, 'pre_option_page_for_posts' ) );
		$original_not_draft      = wpml_test_insert_post( $source_lang );
		$translation_not_draft   = wpml_test_insert_post( $target_lang,
		                                                  'post',
		                                                  $wpml_post_translations->get_element_trid( $original_not_draft ) );
		$original_draft_in_trans = wpml_test_insert_post( $source_lang );
		$translation_draft       = wpml_test_insert_post( $target_lang,
		                                                  'post',
		                                                  $wpml_post_translations->get_element_trid( $original_draft_in_trans ) );
		$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $translation_draft ) );
		add_action( 'parse_query', array( $sitepress, 'parse_query' ) );
		wpml_load_query_filter( true );
		list( $posts ) = $this->posts_for_posts_page( $test_page_name, $source_lang );
		$this->assertCount( 2, $posts );
		list( $posts, $q ) = $this->posts_for_posts_page( $test_page_name, $target_lang );
		$this->assertCount( 1, $posts );
		$post = end( $posts );
		$this->assertEquals( (int) $secondary_page_id, (int) $q->queried_object->ID );
		$this->assertEquals( (int) $translation_not_draft, (int) $post->ID );

		wp_delete_post( $translation_not_draft, true );
		list( $posts ) = $this->posts_for_posts_page( $test_page_name, $target_lang, true );
		feed_links_extra();
		$this->assertCount( 0, $posts );
		$sitepress->switch_lang();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2572
	 * Only allow 'p' and 'page_id' to be converted in $q->query
	 */
	public function test_limit_query_modification() {
		$pid = 152;
		$new_pid = 163;

		$args = array(
			'page_id' => $pid,
			'pagename'    => 'sample-page',
		);
		$q = new WP_Query( $args );
		$this->check_limit_query_modification( $q, $new_pid );

		$args = array(
			'p'       => $pid,
			'name'    => 'test-post',
		);
		$q = new WP_Query( $args );
		$this->check_limit_query_modification( $q, $new_pid );

	}
	
	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2711
	 */
	public function test_redirect_is_not_set_if_pid_doesnt_change() {
		$post_id = 1;
		
		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_settings' )->willReturn( array() );
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array( 'en', 'de' ) );
		$sitepress_mock->method( 'get_current_language' )->willReturn( 'en' );
		
		$post_translations_mock = $this->get_post_translation_mock();
		$post_translations_mock->method( 'get_element_lang_code' )->willReturn( 'en' );
		
		$wpdb_mock = $this->get_wpdb_mock();
		$wpdb_mock->method( 'get_col' )->willReturn( array( $post_id ) );
		
		$subject = new WPML_Page_Name_Query_Filter($sitepress_mock, $post_translations_mock, $wpdb_mock);
		
		$query = new stdClass;
		$query->query_vars = array( 'name' => 'some-name' );
		$query->queried_object_id = $post_id;
		list( $query, $pid) = $subject->filter_page_name( $query );
		
		$this->assertFalse( $pid );
		
		$wpdb_mock = $this->get_wpdb_mock();
		$wpdb_mock->method( 'get_col' )->willReturn( array( $post_id + 1 ) );
		
		$subject = new WPML_Page_Name_Query_Filter($sitepress_mock, $post_translations_mock, $wpdb_mock);
		
		list( $query, $pid) = $subject->filter_page_name( $query );
		
		$this->assertEquals( $post_id + 1, $pid );
	}
	
	public function test_no_matching_page_name() {
		$post_id = 1;
		
		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_settings' )->willReturn( array() );
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array( 'en', 'de' ) );
		$sitepress_mock->method( 'get_current_language' )->willReturn( 'en' );
		
		$post_translations_mock = $this->get_post_translation_mock();
		$post_translations_mock->method( 'get_element_lang_code' )->willReturn( null );
		
		$wpdb_mock = $this->get_wpdb_mock();
		$wpdb_mock->method( 'get_col' )->willReturn( array( $post_id ) );
		
		$subject = new WPML_Page_Name_Query_Filter($sitepress_mock, $post_translations_mock, $wpdb_mock);

		$query = new stdClass;
		$query->query_vars = array( 'name' => 'some-name' );
		list( $filtered_query, $pid) = $subject->filter_page_name( $query );
	
		$this->assertFalse( $pid );
		$this->assertEquals( $query->query_vars[ 'name' ], $filtered_query->query_vars[ 'name' ] );
		$this->assertEquals( $query->query_vars[ 'post_type' ], 'page' );
		
	}

	private function posts_for_posts_page( $test_page_name, $lang, $force_singular = false) {
		global $sitepress, $wpml_post_translations;

		$sitepress->switch_lang( $lang );
		wp_cache_init();
		icl_cache_clear();
		$q = new WP_Query();
		$q->set( 'pagename', $test_page_name );
		$q->set( 'page', "" );
		$q->is_posts_page = true;
		if ( $force_singular === true ) {
			$q->is_singular = true;
		}
		$q->is_home          = true;
		$q->is_page          = false;
		$posts               = $q->get_posts();
		$queried_for_page_id = $q->queried_object_id;
		$this->assertEquals( $lang, $sitepress->get_current_language() );
		$this->assertEquals( $lang, $wpml_post_translations->get_element_lang_code( $queried_for_page_id ) );
		$this->assertEquals( 'page', get_option( 'show_on_front' ) );
		$this->assertEquals( $queried_for_page_id, get_option( 'page_for_posts' ) );

		return array( $posts, $q );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1812
	 *
	 * @param string $source_lang
	 * @param string $target_lang
	 */
	private function check_nested_equal_slugs( $source_lang, $target_lang ) {
		global $wpdb, $sitepress;

		$page_names = array();
		for ( $i = 0; $i < 3; $i ++ ) {
			$page_names[] = rand_str( 10 );
		}
		$page_ids = array_fill_keys( array( $source_lang, $target_lang ), array() );
		foreach ( $page_names as $name ) {
			$source_page_id             = wpml_test_insert_post( $source_lang,
			                                                     'page',
			                                                     false,
			                                                     $name,
			                                                     end( $page_ids[ $source_lang ] ) );
			$page_ids[ $source_lang ][] = $source_page_id;
			$target_page_id             = wpml_test_insert_post( $target_lang,
			                                                     'page',
			                                                     false,
			                                                     $name,
			                                                     end( $page_ids[ $target_lang ] ) );
			$page_ids[ $target_lang ][] = $target_page_id;
			$wpdb->update( $wpdb->posts, array( 'post_name' => $name ), array( 'ID' => $target_page_id ) );
			$wpdb->update( $wpdb->posts, array( 'post_name' => $name ), array( 'ID' => $source_page_id ) );
			clean_post_cache( $source_page_id );
			clean_post_cache( $target_page_id );
		}
		$query_string = join( '/', $page_names );
		foreach ( array( $source_lang, $target_lang ) as $lang ) {
			$sitepress->switch_lang( $lang );
			$this->check_query_changes( $query_string, end( $page_ids[ $lang ] ) );
			$this->check_query_changes( $query_string, end( $page_ids[ $lang ] ), true );
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1985
	 *
	 * @param string $test_page_name
	 * @param int|null $page_id
	 * @param bool $set_object
	 *
	 */
	private function check_query_changes( $test_page_name, $page_id = null, $set_object = false ) {
		/** @var WPML_Query_Filter $wpml_query_filter */
		global $wpml_query_filter;

		foreach ( ( array( 'name', 'pagename' ) ) as $index ) {
			$name_filter = $wpml_query_filter->get_page_name_filter();
			$q           = new WP_Query();
			$q->set( $index, $test_page_name );
			if ( $set_object === true ) {
				$q->queried_object = get_post( $page_id );
			}

			$q_new         = clone( $q );
			list( $q_new ) = $name_filter->filter_page_name( $q_new );
			if ( isset( $page_id ) && $set_object === false ) {
				$this->assertEquals( $page_id, $q_new->query_vars['page_id'] );
				$this->assertFalse( isset( $q_new->queried_object ) );
				$this->assertTrue( $q_new->is_page() );
			} else {
				$this->assertEquals( $q, $q_new );
			}
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1844
	 */
	private function check_three_nested_levels() {
		$this->check_three_slugs( array( 'test', 'test', 'test' ),
		                          array( 'de' => '11', 'en' => '9' ),
		                          'test/test' );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1961
	 */
	private function check_par_child_same_slug() {
		$this->check_three_slugs( array( 'parent', 'child', 'child' ),
		                          array( 'de' => '13', 'en' => '12' ),
		                          'parent/child/child' );
	}

	/**
	 * @param string[] $slugs
	 * @param array    $id_array
	 * @param string   $pagename
	 */
	private function check_three_slugs( $slugs, $id_array, $pagename ) {
		$wpdb_mock = $this->get_wpdb_mock();
		$wpdb_mock->method( 'get_results' )->willReturn( array(
			                                                 (object) array(
				                                                 'ID'          => '9',
				                                                 'post_name'   => $slugs[1],
				                                                 'post_parent' => '5',
				                                                 'parent_name' => $slugs[0],
			                                                 ),
			                                                 (object) array(
				                                                 'ID'          => '11',
				                                                 'post_name'   => $slugs[1],
				                                                 'post_parent' => '7',
				                                                 'parent_name' => $slugs[0],
			                                                 ),
			                                                 (object) array(
				                                                 'ID'          => '13',
				                                                 'post_name'   => $slugs[2],
				                                                 'post_parent' => '11',
				                                                 'parent_name' => $slugs[1],
			                                                 ),
			                                                 (object) array(
				                                                 'ID'          => '12',
				                                                 'post_name'   => $slugs[2],
				                                                 'post_parent' => '9',
				                                                 'parent_name' => $slugs[1],
			                                                 ),
			                                                 (object) array(
				                                                 'ID'          => '7',
				                                                 'post_name'   => $slugs[0],
				                                                 'post_parent' => '0',
				                                                 'parent_name' => null,
			                                                 ),
			                                                 (object) array(
				                                                 'ID'          => '5',
				                                                 'post_name'   => $slugs[0],
				                                                 'post_parent' => '0',
				                                                 'parent_name' => null,
			                                                 ),
		                                                 ) );
		foreach ( $id_array as $current_lang => $correct_child_id ) {
			$sitepress_mock        = $this->get_sitepress_mock();
			$post_translation_mock = $this->get_post_translation_mock();
			$sitepress_mock->method( 'get_active_languages' )->willReturn( array( 'en' => 1, 'de' => 1 ) );
			$sitepress_mock->method( 'get_current_language' )->willReturn( $current_lang );
			$sitepress_mock->method( 'is_translated_post_type' )->willReturn(true);
			$post_translation_mock->method( 'get_element_lang_code' )->willReturnMap( array(
				                                                                          array( 13, 'de' ),
				                                                                          array( 12, 'en' ),
				                                                                          array( 11, 'de' ),
				                                                                          array( 9, 'en' ),
				                                                                          array( 7, 'de' ),
				                                                                          array( 5, 'en' )
			                                                                          ) );
			$subject                = new WPML_Page_Name_Query_Filter( $sitepress_mock,
			                                                           $post_translation_mock,
			                                                           $wpdb_mock );
			$test_query             = new WP_Query();
			$test_query->query      = array(
				'page'     => '',
				'pagename' => $pagename,
			);
			$test_query->query_vars = $test_query->query;
			list($filter_result)    = $subject->filter_page_name( $test_query );
			$this->assertEquals( (string) $correct_child_id, (string) $filter_result->query_vars['page_id'] );
		}
	}

	private function check_limit_query_modification( $q, $new_pid ) {
		global $sitepress;
		$wpdb = $this->get_wpdb_mock();
		$wpdb->method( 'get_col' )->willReturn( array( $new_pid) );
		$post_translation = $this->get_post_translation_mock();
		$post_translation->method( 'get_element_lang_code' )->willReturn( 'en' );

		$q_new         = clone( $q );
		$suject   = new WPML_Page_Name_Query_Filter( $sitepress, $post_translation, $wpdb );
		list( $q_new ) = $suject->filter_page_name( $q_new );

		$this->assertEquals( count( $q->query ), count( $q_new->query ) );
		if ( is_array( $q->query ) ) {
			foreach ( $q->query as $arg => $value ) {
				$allowed_to_change = array( 'p', 'page_id' );
				if ( in_array( $arg, $allowed_to_change ) ) {
					$this->assertEquals( isset( $q->query[ $arg ] ), isset( $q_new->query[ $arg ] ) );
				} else {
					$this->assertEquals( $q->query[ $arg ], $q_new->query[ $arg ] );
				}
			}
		}
	}
	
	function tearDown() {
		delete_option( 'page_for_posts' );
		delete_option( 'page_on_front' );
		delete_option( 'show_on_front' );
		parent::tearDown();
	}
	
}