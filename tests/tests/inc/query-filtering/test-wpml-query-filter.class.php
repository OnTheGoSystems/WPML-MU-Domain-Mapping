<?php

/**
 * Class Test_WPML_Query_Filter
 */
class Test_WPML_Query_Filter extends WPML_UnitTestCase {

	private $custom_post_type_name    = 'custom';
	private $custom_post_type_name_nt = 'nt';

	function test_query_filter_any_type() {
		global $sitepress, $wpml_post_translations, $wp_query;

		$def_lang = $sitepress->get_default_language();
		$sec_lang = 'fr';

		$post_def = wpml_test_insert_post( $def_lang, 'post', false, 'search ' . rand_str() );
		$this->insert_posts( 3, $def_lang );
		$post_sec = wpml_test_insert_post( $sec_lang, 'post', false, 'search ' . rand_str() );
		$this->insert_posts( 3, $sec_lang );

		$this->assertEquals( $def_lang, $wpml_post_translations->get_element_lang_code( $post_def ) );
		$this->assertEquals( $sec_lang, $wpml_post_translations->get_element_lang_code( $post_sec ) );

		$sitepress->switch_lang( $def_lang );
		add_action( 'query_vars', array( $sitepress, 'query_vars' ) );
		wpml_load_query_filter( true );
		$wp_query->parse_query( 'post_type=post' );
		$posts = $wp_query->get_posts();

		foreach ( $posts as $post ) {
			$this->assertEquals( $def_lang, $wpml_post_translations->get_element_lang_code( $post->ID ) );
		}

		$this->check_mixed_query( $sec_lang );

		$wp_query->parse_query( 'post_type=any' );
		$posts = $wp_query->get_posts();
		foreach ( $posts as $post ) {
			$this->assertTrue(
				in_array( $wpml_post_translations->get_element_lang_code( $post->ID ), array( null, $def_lang ) )
			);
		}

		$wp_query->parse_query( 'post_type=post&s=search' );
		$posts = $wp_query->get_posts();
		$this->assertCount( 1, $posts );
		$post_found = array_pop( $posts );
		$this->assertEquals( $post_def, $post_found->ID );

		$sitepress->switch_lang( $sec_lang );

		$wp_query->parse_query( 'post_type=post&s=search' );
		$posts = $wp_query->get_posts();
		$this->assertCount( 1, $posts );
		$post_found = array_pop( $posts );
		$this->assertEquals( $post_sec, $post_found->ID );

		remove_action( 'query_vars', array( $sitepress, 'query_vars' ) );
		global $wpml_query_filter;
		remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10 );
		remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10 );
	}

	public function test_filter_single_type_where() {
		global $wpdb;

		$sitepress    = $this->get_sitepress_mock();
		$current_lang = 'de';
		$sitepress->method( 'get_current_language' )->willReturn( $current_lang );
		$sitepress->method( 'get_translatable_documents' )->willReturn( array( $this->custom_post_type_name => 1 ) );
		$sitepress->method( 'is_translated_post_type' )->willReturnMap( array(
			array(
				$this->custom_post_type_name,
				true
			),
			array( $this->custom_post_type_name_nt, false )
		) );
		$post_translation = $this->get_post_translation_mock();
		$term_translation = $this->get_term_translation_mock();

		$subject = new WPML_Query_Filter( $sitepress, $wpdb, $post_translation, $term_translation );
		$where   = " ";
		$this->assertEquals( $where, $subject->filter_single_type_where( $where, $this->custom_post_type_name_nt ) );
		$this->assertNotEquals( $where, $subject->filter_single_type_where( $where, $this->custom_post_type_name ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1504
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2006
	 */
	public function test_query_for_attachment() {
		global $sitepress, $wpdb;

		$def_lang = $sitepress->get_default_language();
		$sitepress->switch_lang( $def_lang );
		$name          = rand_str();
		$attachment_id = wpml_test_insert_post( $def_lang, 'attachment', false, $name );
		add_filter( 'pre_wpml_is_translated_post_type',
		            array( $this, 'pre_wpml_is_translated_post_type_filter' ),
		            10,
		            2 );
		$this->check_attachment_present( $name, $attachment_id );
		$wpdb->delete( $wpdb->prefix . 'icl_translations', array( 'element_type' => 'post_attachment' ) );
		$this->check_attachment_present( $name, $attachment_id, 0 );
		remove_all_filters( 'pre_wpml_is_translated_post_type' );
		$this->check_attachment_present( $name, $attachment_id );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2326
	 */
	public function test_untranslated_cpt_untranslated_ctax() {
		global $sitepress;

		$ctax_name = 'ctax';
		$cpt_name  = 'cpt';
		wpml_test_reg_custom_post_type( $cpt_name );
		wpml_test_reg_custom_taxonomy( $ctax_name, false, false, array( $cpt_name ) );
		$wp_query = new WP_Query();
		add_action( 'query_vars', array( $sitepress, 'query_vars' ) );
		wpml_load_query_filter( true );
		$term_slug = 'foo';
		$tax_ids   = wpml_test_insert_term( false, $ctax_name, false, $term_slug );
		$cpt_id    = wpml_test_insert_post( false, $cpt_name );
		wp_set_object_terms( $cpt_id, (int) $tax_ids['term_id'], $ctax_name );
		$wp_query->parse_query( 'taxonomy=' . $ctax_name . '&term=' . $term_slug );
		$posts = $wp_query->get_posts();
		$this->assertCount( 1, $posts );
	}

	/**
	 * Creates two nested terms in two languages for a custom taxonomy and attaches
	 * one post to each. The terms as well as the posts are translations for one another
	 * and a query that is setup to include child terms via include_children in the tax
	 * query part of things is ran. It is checked that the child and parent term are included
	 * for original and translated language.
	 *
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2351
	 */
	public function test_translated_cpt_translated_ctax() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress, $wpml_post_translations, $wpml_term_translations;

		$ctax_name = 'ctax';
		$cpt_name  = 'cpt';
		wpml_test_reg_custom_post_type( $cpt_name, true );
		wpml_test_reg_custom_taxonomy( $ctax_name, true, true,
			array( $cpt_name ) );
		$wp_query = new WP_Query();
		add_action( 'query_vars', array( $sitepress, 'query_vars' ) );
		wpml_load_query_filter( true );
		$term_slug = 'foo';
		list( $orig_lang, $trans_lang ) = $this->get_source_and_target_languages( 1 );
		$sitepress->switch_lang( $orig_lang );
		$tax_ids                = wpml_test_insert_term( $orig_lang, $ctax_name,
			false,
			$term_slug );
		$tax_ids_trans          = wpml_test_insert_term( $trans_lang,
			$ctax_name,
			$wpml_term_translations->get_element_trid( (int) $tax_ids['term_taxonomy_id'] ),
			$term_slug . '-trans' );
		$tax_ids_child          = wpml_test_insert_term( $orig_lang, $ctax_name,
			false,
			$term_slug . '-child', (int) $tax_ids['term_id'] );
		$tax_ids_child_trans    = wpml_test_insert_term( $trans_lang,
			$ctax_name,
			$wpml_term_translations->get_element_trid( (int) $tax_ids_child['term_taxonomy_id'] ),
			$term_slug . '-child-trans', (int) $tax_ids_trans['term_id'] );
		$cpt_id                 = wpml_test_insert_post( $orig_lang,
			$cpt_name );
		$cpt_id_trans           = wpml_test_insert_post( $trans_lang,
			$cpt_name, $wpml_post_translations->get_element_trid( $cpt_id ) );
		$cpt_id_on_parent       = wpml_test_insert_post( $orig_lang,
			$cpt_name );
		$cpt_id_on_parent_trans = wpml_test_insert_post( $trans_lang,
			$cpt_name,
			$wpml_post_translations->get_element_trid( $cpt_id_on_parent ) );
		wp_set_object_terms( $cpt_id, (int) $tax_ids_child['term_id'],
			$ctax_name );
		wp_set_object_terms( $cpt_id_on_parent, (int) $tax_ids['term_id'],
			$ctax_name );
		wp_set_object_terms( $cpt_id_trans,
			(int) $tax_ids_child_trans['term_id'],
			$ctax_name );
		wp_set_object_terms( $cpt_id_on_parent_trans,
			(int) $tax_ids_trans['term_id'],
			$ctax_name );
		$wp_query->parse_query(
			array(
				'post_type'      => $cpt_name,
				'no_found_rows'  => true,
				'posts_per_page' => 0,
				'tax_query'      => array(
					'taxonomy'         => $ctax_name,
					'terms'            => (int) $tax_ids['term_id'],
					'include_children' => true
				)
			) );
		$posts_orig = $wp_query->get_posts();
		$this->assertCount( 2, $posts_orig );
		$sitepress->switch_lang( $trans_lang );
		$wp_query = new WP_Query();
		$wp_query->parse_query(
			array(
				'post_type'      => $cpt_name,
				'no_found_rows'  => true,
				'posts_per_page' => 0,
				'tax_query'      => array(
					'taxonomy'         => $ctax_name,
					'terms'            => (int) $tax_ids_trans['term_id'],
					'include_children' => true
				)
			) );
		$posts = $wp_query->get_posts();
		$this->assertCount( 2, $posts );
	}

	/**
	 * Test dummy filter setting the attachment post type as translated
	 *
	 * @param bool   $translated
	 * @param string $type
	 *
	 * @return bool
	 */
	public function pre_wpml_is_translated_post_type_filter( $translated, $type ) {

		return $type === 'attachment' ? true : $translated;
	}

	/**
	 * Checks that one attachment is found for a given name and/or attachment_id
	 *
	 * @param string $name
	 * @param int    $attachment_id
	 * @param int    $count
	 */
	private function check_attachment_present( $name, $attachment_id, $count = 1 ) {
		global $wp_query;

		$query_strings = array(
			'post_type=attachment&name=' . $name,
			'attachment_id=' . $attachment_id
		);
		foreach ( $query_strings as $q_string ) {
			wp_cache_init();
			wp_reset_query();
			wpml_load_query_filter( true );
			$wp_query->parse_query( $q_string );
			$posts = $wp_query->get_posts();
			$this->assertCount( $count, $posts );
		}
	}

	private function check_single_custom_post_in_type( $post_id, $type ) {
		global $wp_query;

		$wp_query->parse_query( 'post_type=' . $type );
		$custom_posts = $wp_query->get_posts();
		$this->assertCount( 1, $custom_posts );
		$custom_post_from_query = array_pop( $custom_posts );
		$this->assertEquals( $post_id, $custom_post_from_query->ID );
	}

	private function check_just_translated_type( $post_type ) {
		global $sitepress;

		$types = $sitepress->get_translatable_documents();
		$this->assertArrayHasKey( $post_type, $types );
	}

	private function check_and_set_translatable( $post_type ) {
		global $sitepress;

		$settings_helper = wpml_load_settings_helper();

		$this->assertFalse( $sitepress->is_translated_post_type( $post_type ) );
		$settings_helper->set_post_type_translatable( $post_type );
		$this->assertTrue( (bool) $sitepress->is_translated_post_type( $post_type ) );
	}

	/**
	 * @param string $sec_lang
	 *
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1314
	 */
	private function check_mixed_query( $sec_lang ) {
		global $sitepress, $wpml_post_translations;

		$def_lang = $sitepress->get_default_language();
		$sitepress->switch_lang( $def_lang );

		$wp_query = new WP_Query();
		wpml_test_reg_custom_post_type( $this->custom_post_type_name );
		wpml_test_reg_custom_post_type( $this->custom_post_type_name_nt );

		$custom_post_id = wpml_test_insert_post( $def_lang, $this->custom_post_type_name );
		$this->check_single_custom_post_in_type( $custom_post_id, $this->custom_post_type_name );
		$this->check_and_set_translatable( $this->custom_post_type_name );
		$this->check_just_translated_type( $this->custom_post_type_name );
		$wp_query->parse_query( 'post_type=' . $this->custom_post_type_name );
		$custom_posts = $wp_query->get_posts();
		$this->assertCount( 1, $custom_posts );
		$this->assertEquals( $def_lang, $wpml_post_translations->get_element_lang_code( $custom_post_id ) );

		$post_factory   = new WP_UnitTest_Factory_For_Post();
		$nt_custom_post = $post_factory->create_and_get( array( 'post_type' => $this->custom_post_type_name_nt ) );
		$this->check_single_custom_post_in_type( $custom_post_id, $this->custom_post_type_name );
		$this->check_single_custom_post_in_type( $nt_custom_post->ID, $this->custom_post_type_name_nt );

		$wp_query = new WP_Query(
			array( 'post_type' => array( $this->custom_post_type_name, $this->custom_post_type_name_nt ) )
		);
		$posts    = $wp_query->get_posts();
		$this->assertCount( 2, $posts );

		$sitepress->switch_lang( $sec_lang );

		$wp_query = new WP_Query(
			array( 'post_type' => array( $this->custom_post_type_name, $this->custom_post_type_name_nt ) )
		);
		$posts    = $wp_query->get_posts();
		$this->assertCount( 1, $posts );
		$this->check_single_custom_post_in_type( $nt_custom_post->ID, $this->custom_post_type_name_nt );

		$sitepress->switch_lang( $def_lang );
	}

	/**
	 * Inserts posts for a specific language
	 *
	 * @param int    $count number of posts
	 * @param string $lang language code
	 */
	private function insert_posts( $count, $lang ) {
		for ( $i = 0; $i < $count; $i ++ ) {
			wpml_test_insert_post( $lang, 'post', false, rand_str() );
		}
	}
}