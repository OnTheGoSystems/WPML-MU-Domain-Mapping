<?php

/**
 * Class Test_WPML_Query_Parser
 */
class Test_WPML_Query_Parser extends WPML_UnitTestCase {
	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/comp-921
	 */
	public function test_parse_query() {
		global $sitepress, $wpml_query_filter;
		$subject = new WPML_Query_Parser( $sitepress, $wpml_query_filter );

		$query = new WP_Query();
		$query->set( 'name', 'foo' );
		foreach ( array( false, array( 'post', 'page' ) ) as $type_setting ) {
			$query->set( 'post_type', $type_setting );
			$query = $subject->parse_query( $query );
			$this->assertEquals( 'foo', $query->get( 'name' ) );
		}
	}

	/**
	 * Checks that we do not alter the query for backend requests
	 */
	public function test_off_on_admin() {
		global $sitepress, $wpml_query_filter;
		$subject = new WPML_Query_Parser( $sitepress, $wpml_query_filter );
		set_current_screen( 'dashboard' );
		$query = new WP_Query();
		$query->set( 'name', 'foo' );
		$this->assertEquals( $query, $subject->parse_query( $query ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2179
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2330
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2786
	 */
	public function test_permalink_redirect() {
		$post_id          = rand( 100, 1000 );
		$parent_id        = $post_id - 1;
		$post_type        = 'topic';
		$post_name        = rand_str( 10 );
		$correct_uri      = '/forumslug/' . $post_name;
		$permalink        = 'http://example.org' . $correct_uri;
		$term_translation = $this->get_term_translation_mock();
		$post_translation = $this->get_post_translation_mock();
		foreach (
			array(
				array(
					'request_uri' => $correct_uri,
					'times'       => $this->never(),
				),
				array(
					'request_uri' => '/' . $post_name,
					'times'       => $this->once(),
				),
				array(
					'request_uri' => '/' . $post_name . '?test=test',
					'times'       => $this->once(),
				),
				array(
					'request_uri' => rand_str() . '/' . $post_name,
					'times'       => $this->once(),
				),
				array(
					'request_uri' => $correct_uri . '/page/5',
					'times'       => $this->never(),
				),
			) as $example
		) {
			$query       = $this->get_wp_query_mock();
			$wp_api_mock = $this->get_wp_api_mock();
			$wp_api_mock->method( 'get_post' )->willReturnMap( array(
				array(
					$post_id,
					OBJECT,
					'raw',
					(object) array(
						'post_name'   => $post_name,
						'post_parent' => $parent_id
					)
				)
			) );
			$request_uri = $example['request_uri'];
			$wp_api_mock->method( 'get_permalink' )
			            ->willReturn( $permalink );
			$wp_api_mock
				->expects( $example['times'] )->method( 'wp_safe_redirect' )
				->with( $this->isType( 'string' ), $this->equalTo( 301 ) );
			$sitepress = $this->get_sitepress_mock( $wp_api_mock );
			$sitepress->method('term_translations')->willReturn($term_translation);
			$sitepress->method('post_translations')->willReturn($post_translation);
			$query->method( 'is_main_query' )->willReturn( true );
			$query->method( 'get' )->willReturnMap( array(
				array(
					'post_type',
					'',
					'topic'
				),
				array( $post_type, '', $post_name )
			) );
			$util_404_mock = $this->get_404_util_mock();
			$util_404_mock->method( 'guess_cpt_by_name' )->willReturn( array(
				$post_name,
				$post_type,
				true
			) );
			$page_name_filter = $this->get_page_name_filter_mock();
			$page_name_filter->method( 'filter_page_name' )->willReturn( array(
				$query,
				$post_id
			) );
			$query_filter     = $this->get_query_filter_mock();
			$query_filter->method( 'get_404_util' )->willReturn( $util_404_mock );
			$query_filter->method( 'get_page_name_filter' )->willReturn( $page_name_filter );
			$_SERVER['REQUEST_URI'] = $request_uri;
			$subject                = new WPML_Query_Parser( $sitepress, $query_filter );
			$subject->parse_query( $query );
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1887
	 */
	public function test_parse_cpt_and_custom_tax() {
		global $sitepress, $wpml_query_filter, $icl_adjust_id_url_filter_off, $iclTranslationManagement;
		remove_all_filters('icl_current_language');
		$icl_adjust_id_url_filter_off = false;
		$def_lang = 'en';
		$sec_lang = 'de';
		$sitepress->switch_lang( $def_lang );
		$sitepress->set_setting( 'auto_adjust_ids', 1 );
		$sitepress->set_term_filters_and_hooks();
		$term_translations = $sitepress->term_translations();
		$subject = new WPML_Query_Parser( $sitepress, $wpml_query_filter );
		$c_tax  = 'product_cat_test';
		$c_type = 'product';
		wpml_test_reg_custom_post_type( $c_type, true );
		wpml_test_reg_custom_taxonomy( $c_tax, true, true, array( $c_type ) );
		$iclTranslationManagement->init();
		$product_cat    = wpml_test_insert_term( $def_lang, $c_tax );
		$trid_cat = $term_translations->get_element_trid( $product_cat['term_taxonomy_id'] );
		$translated_cat = wpml_test_insert_term( $sec_lang, $c_tax, $trid_cat );
		$orig_product_cat_term_id = (int) $product_cat['term_id'];
		$trans_product_cat_term_id = (int) $translated_cat['term_id'];
		$this->assertEquals( $trans_product_cat_term_id, $orig_product_cat_term_id + 1 );
		$query_arr = array(
			'post_type'      => $c_type,
			'posts_per_page' => 5,
			'fields'         => 'ids',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => $c_tax,
					'terms'    => array(
						$orig_product_cat_term_id
					),
					'operator' => 'IN'
				)
			)
		);
		$sitepress->switch_lang( $def_lang );
		$query = new WP_Query( $query_arr );
		$parsed_query = $subject->parse_query( $query );
		$this->assertEquals( array( $orig_product_cat_term_id ), $parsed_query->query_vars['tax_query'][0]['terms'] );
		$sitepress->switch_lang( $sec_lang );
		$query        = new WP_Query( $query_arr );
		$parsed_query = $subject->parse_query( $query );
		$this->assertFalse( $icl_adjust_id_url_filter_off );
		$this->assertEquals( array( $trans_product_cat_term_id ), $parsed_query->query_vars['tax_query'][0]['terms'] );
		$this->check_standard_post_custom_tax( $subject );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2032
	 */
	public function test_untranslated_posts_page() {
		global $sitepress;

		$def_lang = $sitepress->get_default_language();
		wpml_load_query_filter( true );
		add_filter( 'pre_option_page_for_posts', array( $sitepress, 'pre_option_page_for_posts' ) );
		add_action( 'parse_query', array( $sitepress, 'parse_query' ) );
		$sitepress->switch_lang( $def_lang );

		$post_id_orig = wpml_test_insert_post( $def_lang, 'post' );
		$page_name    = 'news';
		$page_id_orig = wpml_test_insert_post( $def_lang, 'page', false, $page_name );
		update_option( 'page_for_posts', $page_id_orig );
		update_option( 'show_on_front', 'page');

		wp_cache_init();
		$q = new WP_Query();
		$q->set( 'pagename', $page_name );
		$q->is_home     = true;
		$q->is_singular = false;
		$q->is_page     = false;
		$posts          = $q->get_posts();
		$post           = end( $posts );
		$this->assertEquals( $post_id_orig, $post->ID );
		$this->assertEquals( '', $q->get( 'name' ) );

		$this->check_untranslated_no_parent_adjust();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2338
	 */
	private function check_untranslated_no_parent_adjust() {
		$term_translation     = $this->get_term_translation_mock();
		$post_translation     = $this->get_post_translation_mock();
		$parent_id            = 4;
		$translated_parent_id = 5;
		$untranslated_type    = 'book';
		$post_translation->method( 'element_id_in' )->willReturnMap( array(
			array(
				$parent_id,
				'de',
				true,
				$translated_parent_id
			)
		) );
		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_current_language' )->willReturn( 'de' );
		$sitepress->method( 'get_default_language' )->willReturn( 'en' );
		$sitepress->method( 'is_translated_post_type' )->willReturnMap( array(
			array( 'post', true ),
			array( $untranslated_type, false )
		) );
		$wpdb         = $this->get_wpdb_mock();
		$query_filter = $this->get_query_filter_mock();
		$wp_query     = new WP_Query();
		$wp_query->set( 'post_parent', $parent_id );
		$wp_query->set( 'post_type', 'post' );
		$sitepress->method('wpdb')->willReturn($wpdb);
		$sitepress->method('term_translations')->willReturn($term_translation);
		$sitepress->method('post_translations')->willReturn($post_translation);
		$subject  = new WPML_Query_Parser( $sitepress, $query_filter );
		$wp_query = $subject->parse_query( $wp_query );
		$this->assertEquals( $translated_parent_id, $wp_query->query_vars['post_parent'] );
		$wp_query->set( 'post_type', $untranslated_type );
		$book_parent_id = rand( 100, 1000 );
		$wp_query->set( 'post_parent', $book_parent_id );
		$wp_query = $subject->parse_query( $wp_query );
		$this->assertEquals( $book_parent_id, $wp_query->query_vars['post_parent'] );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2275
	 *
	 * @param WPML_Query_Parser $subject
	 */
	private function check_standard_post_custom_tax( $subject ) {
		global $sitepress;

		$orig_term     = wpml_test_insert_term( 'en', 'category', false, 'taxoslug' );
		$trid_term     = $sitepress->term_translations()->get_element_trid( $orig_term['term_taxonomy_id'] );
		$trans_term    = wpml_test_insert_term( 'de', 'category', $trid_term, 'taxoslugde' );
		$post_id       = wpml_test_insert_post( 'en' );
		$post_trid     = $sitepress->post_translations()->get_element_trid( $post_id );
		$post_id_trans = wpml_test_insert_post( 'de', 'post', $post_trid );
		wp_add_object_terms( $post_id, (int) $orig_term['term_id'], 'category' );
		wp_add_object_terms( $post_id_trans, (int) $trans_term['term_id'], 'category' );

		$args  = array(
				'posts_per_page'   => - 1,
				'offset'           => 0,
				'post_type'        => 'post',
				'tax_query'        => array(
						array(
								'taxonomy' => 'category',
								'field'    => 'slug',
								'terms'    => 'taxoslug',
						)
				),
				'post_status'      => 'publish',
				'suppress_filters' => false,
		);
		$query = new WP_Query( $args );
		$query = $subject->parse_query( $query );
		$sitepress->switch_lang( 'en' );
		$entries = $query->get_posts();
		$this->assertCount( 1, $entries );
	}
	
	function tearDown() {
		delete_option( 'page_for_posts' );
		delete_option( 'page_on_front' );
		delete_option( 'show_on_front' );
		parent::tearDown();
	}
}