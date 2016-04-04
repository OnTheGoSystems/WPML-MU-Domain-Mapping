<?php

class Test_LS_Languages extends WPML_UnitTestCase {

	function setUp() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress;
		parent::setUp();
		$sitepress->set_term_filters_and_hooks();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1539
	 */
	function test_get_ls_languages() {
		global $sitepress, $wp_query, $wpml_post_translations, $wpdb;

		$wp_query = null;
		$sitepress->set_wp_query();
		$wp_query = new WP_Query();
		$wp_query->set( 'post_type', array( 'post', 'product', 'book' ) );
		$user_factory = new WP_UnitTest_Factory_For_User();
		/** @var WP_User $new_user */
		$new_user = $user_factory->create_and_get();
		$this->assertGreaterThan( 0, $new_user->ID );
		$wp_query->set( 'author', $new_user->ID );
		$wp_query->get_posts();
		$def_lang   = $sitepress->get_default_language();
		$sec_lang   = 'de';
		$third_lang = 'fr';
		$sitepress->switch_lang( $def_lang );
		$this->assertCount( 1, $sitepress->get_ls_languages() );
		$post_id_orig  = wpml_test_insert_post( $def_lang );
		$trid          = $wpml_post_translations->get_element_trid( $post_id_orig );
		$post_id_trans = wpml_test_insert_post( $sec_lang, 'post', $trid, false );
		$wpdb->update( $wpdb->posts, array( 'post_author' => $new_user->ID ), array( 'ID' => $post_id_orig ) );
		$wpdb->update( $wpdb->posts, array( 'post_author' => $new_user->ID ), array( 'ID' => $post_id_trans ) );
		wpml_test_reg_custom_post_type( 'book', true );
		icl_cache_clear();
		wp_cache_init();

		$wp_query->set( 'author', $new_user->user_login );
		$wp_query->set( 'post_type', array( 'post', 'product', 'book' ) );
		$wp_query->get_posts();
		$this->assertCount( 2, $sitepress->get_ls_languages() );
		$book_id = wpml_test_insert_post( $third_lang, 'book' );
		$wpdb->update( $wpdb->posts, array( 'post_author' => $new_user->ID ), array( 'ID' => $book_id ) );
		icl_cache_clear();
		wp_cache_init();
		$this->assertCount( 3, $sitepress->get_ls_languages() );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2032
	 */
	public function test_cpt_archive_link_display() {
		global $sitepress, $wpml_post_translations, $wp_actions;
		$bk_wp_actions = $wp_actions;
		$post_type  = 'book';
		$def_lang   = $sitepress->get_default_language();
		$sec_lang   = 'de';
		$third_lang = 'fr';

		// Register CPT and add one
		wpml_test_reg_custom_post_type( $post_type, true );
		$post_id_orig = wpml_test_insert_post( $def_lang, $post_type );
		$trid = $wpml_post_translations->get_element_trid( $post_id_orig );

		// Test on default lang
		$sitepress->switch_lang( $def_lang );
		$url = get_post_type_archive_link( $post_type );
		$this->switch_to_front( $url );
		unset( $wp_actions['wp'] );
		$languages = $sitepress->get_ls_languages();
		$this->assertCount( 1, $languages );

		// Test with translation on second lang
		wpml_test_insert_post( $sec_lang, 'book', $trid, false );
		icl_cache_clear();
		$sitepress->switch_lang( $sec_lang );
		$url = get_post_type_archive_link( $post_type );
		$this->switch_to_front( $url );
		unset( $wp_actions['wp'] );
		$languages = $sitepress->get_ls_languages();
		$this->assertCount( 2, $languages );

		// Test with translation on third lang
		wpml_test_insert_post( $third_lang, 'book' );
		icl_cache_clear();
		$sitepress->switch_lang( $third_lang );
		$url = get_post_type_archive_link( $post_type );
		$this->switch_to_front( $url );
		unset( $wp_actions['wp'] );
		$languages = $sitepress->get_ls_languages();
		$this->assertCount( 3, $languages );

		// Restore
		$wp_actions = $bk_wp_actions;
	}

	public function test_default_permalinks_langs_in_subdir_domains() {
		global $sitepress, $wpml_post_translations, $wp_query;

		$sitepress = new SitePress();
		$this->switch_to_langs_in_domains( WP_TESTS_DOMAIN . '/subdir', 'http:', '%year%/%monthnum%/%day%/%postname%' );
		list( $orig_lang, $translated_lang ) = $this->get_source_and_target_languages( 1 );
		$original    = wpml_test_insert_post( $orig_lang, 'post' );
		$translation = wpml_test_insert_post( $translated_lang, 'post',
			$wpml_post_translations->get_element_trid( $original ) );
		wp_cache_init();
		wp_load_core_site_options();
		$wp_query = new WP_Query();
		$wp_query->init();
		$wp_query->parse_query( array( 'p' => $original ) );
		$wp_query->get_posts();
		$ls_rows = $sitepress->get_ls_languages();
		$this->assertCount( 2, $ls_rows );
		foreach ( $ls_rows as $row ) {
			$this->assertFalse( strpos( $row['url'], 'subdir/subdir' ) );
		}
	}

	/**
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2459
	 */
	public function test_plain_permalinks() {
		global $sitepress, $wpml_post_translations, $wp_query;

		$sitepress = new SitePress();
		$request_lang           = 'ru';
		$_GET['lang']           = $request_lang;
		$_SERVER['REQUEST_URI'] = '/?lang=' . $request_lang;
		$this->switch_to_langs_as_params();
		$this->assertEquals( $request_lang, $sitepress->get_current_language() );
		$trans_lang  = 'de';
		$original    = wpml_test_insert_post( $request_lang, 'page' );
		$translation = wpml_test_insert_post( $trans_lang, 'page', $wpml_post_translations->get_element_trid( $original ) );
		wp_cache_init();
		$wp_query = new WP_Query();
		$wp_query->init();
		$wp_query->parse_query( array( 'page_id' => $original ) );
		$wp_query->is_single = true;
		$wp_query->get_posts();
		$ls_rows = $sitepress->get_ls_languages();
		$this->assertTrue( (bool) strpos( $ls_rows[ $trans_lang ]['url'], '?page_id=' . $translation ) );
		$sitepress->switch_lang();
		$this->assertEquals( $request_lang, $sitepress->get_current_language() );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1628
	 */
	public function test_cpt_archive_slug_translation() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wpml_language_resolution, $sitepress, $wp_query, $wp_rewrite, $sitepress_settings;

		$cpt = 'book';
		$this->turn_slug_translation_on_for( $cpt );
		$def_lang = $sitepress->get_default_language();
		wpml_test_reg_custom_post_type( $cpt, true );
		$sitepress->switch_lang( $def_lang );
		$active_lang_codes = $wpml_language_resolution->get_active_language_codes();

		foreach ( $active_lang_codes as $code ) {
			wpml_test_insert_post( $code, $cpt );
		}

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( "%postname%" );
		create_initial_taxonomies();
		$wp_rewrite->flush_rules();
		$this->flush_cache();

		$sitepress_settings[ 'language_negotiation_type' ] = 1;
		load_wpml_url_converter(
			$sitepress_settings,
			1,
			$def_lang
		);

		$wp_query = new WP_Query();
		$wp_query->init();
		$wp_query->set( 'post_type', $cpt );
		$wp_query->get_posts();
		$sitepress->set_wp_query();
		$this->assertTrue( is_post_type_archive() );
		$this->assertFalse( is_author() );

		add_filter( 'wpml_get_translated_slug', array( $this, 'stub_cpt_slug_filter' ), 10, 3 );
		$ls_languages      = $sitepress->get_ls_languages();
		$this->assertEquals( count( $active_lang_codes ), count( $ls_languages ) );
		foreach ( $active_lang_codes as $code ) {
			$expected_slug = $def_lang === $code ? $cpt : $cpt . $code;
			$this->assertTrue( (bool) strpos( $ls_languages[ $code ][ 'url' ], '/' . $expected_slug ) );
		}
		remove_all_filters( 'wpml_get_translated_slug' );
	}

	function stub_cpt_slug_filter( $slug, $post_type, $language_code ) {

		return $language_code !== 'en'	? $slug . $language_code : $slug;
	}

	private function turn_slug_translation_on_for( $post_type ) {

		$slug_translation_settings                          = wpml_get_setting_filter( false,
																					   'posts_slug_translation',
																					   array() );
		$slug_translation_settings[ 'on' ]                  = 1;
		$slug_translation_settings[ 'types' ]               = isset( $slug_translation_settings[ 'types' ] )
			? $slug_translation_settings[ 'types' ] : array();
		$slug_translation_settings[ 'types' ][ $post_type ] = 1;
		icl_set_setting( 'posts_slug_translation', $slug_translation_settings, true );
	}
}