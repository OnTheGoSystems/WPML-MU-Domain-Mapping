<?php

class Test_URL_Filters extends WPML_UnitTestCase {

	private $tree_slug = 'tree1-slug-nice';

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1972
	 */
	function test_get_edit_post_link() {
		$sitepress         = $this->get_sitepress_mock();
		$post_translations = $this->get_post_translation_mock();
		$url_converter     = $this->get_url_converter_mock();
		$subject           = new WPML_URL_Filters( $post_translations, $url_converter, $sitepress );

		$link_raw          = 'http://example.com/wp-admin/post.php?post=4&action=edit';
		$link_untranslated = $subject->get_edit_post_link( $link_raw, 4 );
		$this->assertEquals( $link_raw, $link_untranslated );

		$lang_test = 'fr';
		$post_translations->method( 'get_element_lang_code' )->willReturn( $lang_test );
		$subject         = new WPML_URL_Filters( $post_translations, $url_converter, $sitepress );
		$link_translated = $subject->get_edit_post_link( $link_raw, 4 );
		$this->assertEquals( $link_untranslated . '&lang=' . $lang_test, html_entity_decode( $link_translated ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2027
	 */
	public function test_filter_nested_categories() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_post_translations, $wpdb, $wpml_term_translations, $sitepress;

		remove_all_filters( 'post_link' );
		$orig_lang    = 'en';
		$sec_lang     = 'de';
		$post_type    = 'post';
		$taxonomy     = 'category';
		$orig_post_id = wpml_test_insert_post( $orig_lang, $post_type );
		$orig_name    = rand_str( 10 );
		$sec_post_id  = wpml_test_insert_post( $sec_lang, $post_type, $wpml_post_translations->get_element_trid( $orig_post_id ) );
		$sec_name     = rand_str( 10 );
		set_current_screen( 'front' );
		foreach (
			array(
				$orig_post_id => $orig_name,
				$sec_post_id  => $sec_name
			) as $post_id => $name
		) {
			$wpdb->update( $wpdb->posts, array( 'post_name' => $name ), array( 'ID' => $post_id ) );
		}
		$names           = array(
			$orig_lang => array( 'parentorig', 'childorig', $orig_name ),
			$sec_lang  => array( 'parentsec', 'childsec', $sec_name )
		);
		$orig_parent_cat = wpml_test_insert_term( $orig_lang, $taxonomy, false, $names[ $orig_lang ][0] );
		$sec_parent_cat  = wpml_test_insert_term( $sec_lang, $taxonomy, $wpml_term_translations->get_element_trid( $orig_parent_cat['term_taxonomy_id'] ), $names[ $sec_lang ][0] );
		$orig_child_cat  = wpml_test_insert_term( $orig_lang, $taxonomy, false, $names[ $orig_lang ][1], $orig_parent_cat['term_id'] );
		$sec_child_cat   = wpml_test_insert_term( $sec_lang, $taxonomy, $wpml_term_translations->get_element_trid( $orig_child_cat['term_taxonomy_id'] ), $names[ $sec_lang ][1], $sec_parent_cat['term_id'] );
		wp_set_object_terms( $orig_post_id, (int) $orig_child_cat['term_id'], $taxonomy );
		wp_set_object_terms( $sec_post_id, (int) $sec_child_cat['term_id'], $taxonomy );
		remove_all_filters( 'post_link' );
		$this->switch_to_langs_in_dirs( '/%category%/%postname%/' );
		$sitepress->set_setting( 'auto_adjust_ids', false, true );
		wp_cache_init();
		$orig_permalink = get_permalink( $orig_post_id );
		$sec_permalink  = get_permalink( $sec_post_id );
		$sitepress->switch_lang( $orig_lang );
		$this->assertNotFalse( strpos( $orig_permalink, join( '/', $names[ $orig_lang ] ) ) );
		$sitepress->switch_lang( $sec_lang );
		$this->assertNotFalse( strpos( $sec_permalink, join( '/', $names[ $sec_lang ] ) ) );
		$sitepress->set_setting( 'auto_adjust_ids', true, true );
		$sitepress->set_term_filters_and_hooks();
		wp_cache_init();
		$sitepress->switch_lang( $orig_lang );
		$permalink = get_permalink( $orig_post_id );
		$this->assertNotFalse( strpos( $permalink, join( '/', $names[ $orig_lang ] ) ) );
		$this->assertFalse( strpos( $permalink, $orig_lang . '/' . join( '/', $names[ $orig_lang ] ) ) );
		$this->assertFalse( strpos( $permalink, $sec_lang . '/' . join( '/', $names[ $orig_lang ] ) ) );
		$sitepress->switch_lang( $sec_lang );
		wp_cache_init();
		$this->assertNotFalse( strpos( get_permalink( $orig_post_id ), $sec_lang . '/' . join( '/', $names[ $sec_lang ] ) ) );
		$sitepress->switch_lang( $orig_lang );
		wp_cache_init();
		$permalink = get_permalink( $sec_post_id );
		$this->assertNotFalse( strpos( $permalink, join( '/', $names[ $orig_lang ] ) ) );
		$this->assertFalse( strpos( $permalink, $orig_lang . '/' . join( '/', $names[ $orig_lang ] ) ) );
		$this->assertFalse( strpos( $permalink, $sec_lang . '/' . join( '/', $names[ $orig_lang ] ) ) );
		$sitepress->switch_lang( $sec_lang );
		wp_cache_init();
		$this->assertNotFalse( strpos( get_permalink( $sec_post_id ), $sec_lang . '/' . join( '/', $names[ $sec_lang ] ) ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1501
	 */
	function test_filter_root_permalink() {
		/** @var WP_Rewrite $wp_rewrite */
		global $sitepress_settings, $sitepress, $wp_rewrite, $wpml_url_filters, $wpml_post_translations;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( "%postname%" );
		create_initial_taxonomies();
		$wp_rewrite->flush_rules();
		$this->flush_cache();

		$def_lang = $sitepress->get_default_language();
		$page     = wpml_test_insert_post( $def_lang, 'page', false, 'rootpage' );
		// Root page
		$sitepress_settings['urls']['directory_for_default_language'] = 1;
		$sitepress_settings['urls']['root_page']                      = $page;
		$sitepress_settings['urls']['show_on_root']                   = 'page';

		icl_set_setting( 'urls', $sitepress_settings['urls'], true );

		$converter = load_wpml_url_converter(
				$sitepress_settings,
				1,
				$def_lang
		);

		$root_page_permalink_orig = get_permalink( $page );
		$abs_home                 = $converter->get_abs_home();

		$this->assertNotEquals( $abs_home, $root_page_permalink_orig );

		$wpml_url_filters    = new WPML_URL_Filters( $wpml_post_translations, $converter, $sitepress );
		$root_page_permalink = $wpml_url_filters->filter_root_permalink( $root_page_permalink_orig );

		$this->assertEquals( trailingslashit( $abs_home ), $root_page_permalink );
		$this->assertEquals(
				trailingslashit( $abs_home ) . '2/',
				$wpml_url_filters->filter_root_permalink( $root_page_permalink_orig . '/2/' )
		);

		$sitepress->switch_lang( 'de' );
		$this->assertEquals( trailingslashit( $abs_home ) . 'de/', trailingslashit( get_home_url() ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1455
	 */
	public function test_post_type_archive_link_filter() {
		/** @var WP_Rewrite $wp_rewrite */

		global $sitepress, $wp_rewrite;
		$this->register_cpt_tree ();
		$settings_helper = wpml_load_settings_helper ();
		$default_lang_code                                                = $sitepress->get_setting (
			'default_language'
		);
		$converter                                                        = $this->switch_to_langs_in_dirs();

		add_filter ( 'post_type_archive_link', array( $sitepress, 'post_type_archive_link_filter' ), 10, 2 );

		$sitepress->switch_lang ( $default_lang_code );
		$abs_home                                                         = $converter->get_abs_home ();

		$this->assertEquals(
			trailingslashit( trailingslashit( $abs_home ) . $this->tree_slug ),
			trailingslashit( get_post_type_archive_link( 'tree' ) )
		);

		$settings_helper->set_post_type_translatable( 'tree' );
		$settings_helper->activate_slug_translation( 'tree' );

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( "%postname%" );
		create_initial_taxonomies();
		$wp_rewrite->flush_rules();
		$this->flush_cache();

		$this->assertEquals(
			trailingslashit( trailingslashit( $abs_home ) . $this->tree_slug ),
			trailingslashit( get_post_type_archive_link( 'tree' ) )
		);

		$sec_lang = 'fr';
		$sitepress->switch_lang( $sec_lang );
		$this->assertEquals(
			trailingslashit( trailingslashit( $abs_home ) . $sec_lang . '/' . $this->tree_slug ),
			trailingslashit( get_post_type_archive_link( 'tree' ) )
		);

		$this->check_cpt_archive_non_pretty( $abs_home, $default_lang_code );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1720
	 */
	public function test_no_trailing_slash_when_param() {
		global $sitepress, $wpml_url_converter, $wpml_post_translations;

		$post_lang               = 'de';
		$post_id                 = wpml_test_insert_post( $post_lang );
		$wpml_url_filters        = new WPML_URL_Filters( $wpml_post_translations, $wpml_url_converter, $sitepress );
		$_POST['action']         = 'sample-permalink';
		$_GET['lang']            = $post_lang;
		$_SERVER['HTTP_REFERER'] = trailingslashit( get_home_url() ) . '/wp-admin/lang=' . $post_lang;
		$sitepress->switch_lang( $post_lang );
		$filtered_url = $wpml_url_filters->permalink_filter( get_home_url() . '?p=' . $post_id . '&lang=' . $post_lang . '/',
		                                                     $post_id );
		$this->assertStringEndsWith( 'lang=' . $post_lang, $filtered_url );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1787
	 *
	 * @param string $abs_home
	 * @param string $default_lang_code
	 */
	private function check_cpt_archive_non_pretty( $abs_home, $default_lang_code ) {
		global $sitepress;

		$this->switch_to_langs_as_params();

		$sitepress->switch_lang( $default_lang_code );
		$this->assertEquals(
			trailingslashit( $abs_home ) . '?post_type=tree',
			get_post_type_archive_link( 'tree' )
		);
	}

	private function register_cpt_tree() {

		$labels = array(
			'name'               => _x ( 'Trees', 'tree' ),
			'singular_name'      => _x ( 'Tree', 'tree' ),
			'add_new'            => _x ( 'Add New', 'tree' ),
			'add_new_item'       => _x ( 'Add New Tree', 'tree' ),
			'edit_item'          => _x ( 'Edit Tree', 'tree' ),
			'new_item'           => _x ( 'New Tree', 'tree' ),
			'view_item'          => _x ( 'View Tree', 'tree' ),
			'search_items'       => _x ( 'Search Trees', 'tree' ),
			'not_found'          => _x ( 'No trees found', 'tree' ),
			'not_found_in_trash' => _x ( 'No trees found in Trash', 'tree' ),
			'parent_item_colon'  => _x ( 'Parent Tree:', 'tree' ),
			'menu_name'          => _x ( 'Trees', 'tree' ),
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'excerpt', 'comments', 'page-attributes' ),
			'taxonomies'          => array( 'category' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => array(
				'slug'       => $this->tree_slug,
				'with_front' => true,
				'feeds'      => true,
				'pages'      => true
			),
			'capability_type'     => 'post'
		);

		register_post_type ( 'tree', $args );
	}

	/**
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1556
	 */
	function test_filter_home_url() {
		global $sitepress_settings, $wpml_url_filters, $wpml_url_converter, $sitepress, $wpml_post_translations;

		$fr_domain = 'http://example.fr';
		$de_domain = 'http://example.de';

		icl_set_setting( 'language_domains', array( 'de' => $de_domain, 'fr' => $fr_domain ), true );
		icl_set_setting( 'language_negotiation_type', 2, true );
		$current_lang       = icl_get_setting( 'default_language' );
		$wpml_url_converter = load_wpml_url_converter( $sitepress_settings, "", $current_lang );
		$wpml_url_filters   = new WPML_URL_Filters( $wpml_post_translations, $wpml_url_converter, $sitepress );
		$site_url_initial   = get_site_url();

		$_SERVER['SERVER_NAME'] = $fr_domain;
		$_SERVER['REQUEST_URI'] = '/';
		$this->assertEquals( $fr_domain, get_home_url() );
		$this->assertEquals( $site_url_initial, get_site_url() );

		$_SERVER['SERVER_NAME'] = $de_domain;
		$_SERVER['REQUEST_URI'] = '/';
		$this->assertEquals( $de_domain, get_home_url() );
		$this->assertEquals( $site_url_initial, get_site_url() );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2763
	 */
	public function test_get_date_link_functions() {
		global $sitepress;
		$def_lang     = $sitepress->get_default_language();
		$active_langs = $sitepress->get_active_languages();
		unset( $active_langs[ $def_lang ] );
		$sec_lang     = array_rand( $active_langs );

		// Backup and fix server globals
		$bk_request_uri = $_SERVER['REQUEST_URI'];
		$bk_server_name = $_SERVER['SERVER_NAME'];
		$bk_home        = get_option( 'home' );

		$this->check_date_link_for_language_in_dirs( $def_lang, $sec_lang );
		$this->check_date_link_for_language_in_domains( $def_lang, $sec_lang );
		$this->check_date_link_for_language_as_params( $def_lang, $sec_lang );

		// Restore server globals
		$_SERVER['REQUEST_URI'] = $bk_request_uri;
		$_SERVER['SERVER_NAME'] = $bk_server_name;
		update_option( 'home', $bk_home );
		update_option( 'siteurl', $bk_home );
		$sitepress->switch_lang( $def_lang );
		$this->switch_to_front( $bk_home );
	}

	/**
	 * @param $def_lang
	 * @param $sec_lang
	 */
	private function check_date_link_for_language_in_dirs( $def_lang, $sec_lang ) {
		global $sitepress;
		$this->switch_to_langs_in_dirs();
		$sitepress->switch_lang( $def_lang );
		$home_url = trailingslashit( get_option( 'home' ) );
		$this->check_links_with_language_negotiation( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY, $home_url );
		$sitepress->switch_lang( $sec_lang );
		$home_url_sec = trailingslashit( $home_url . $sec_lang );
		$this->check_links_with_language_negotiation( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY, $home_url_sec );
	}

	/**
	 * @param $def_lang
	 * @param $sec_lang
	 */
	private function check_date_link_for_language_in_domains( $def_lang, $sec_lang ) {
		global $sitepress;
		$this->switch_to_langs_in_domains();
		$sitepress->switch_lang( $def_lang );
		$home_url = trailingslashit( get_option( 'home' ) );
		$this->check_links_with_language_negotiation( WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN, $home_url );
		$sitepress->switch_lang( $sec_lang );
		$domains      = $sitepress->get_setting( 'language_domains' );
		$home_url_sec = isset( $domains[ $sec_lang ] )
			? trailingslashit( 'http://' . $domains[ $sec_lang ] ) : 'http://' . $sec_lang . '.example.com/';
		$this->check_links_with_language_negotiation( WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN, $home_url_sec );
	}

	/**
	 * @param $def_lang
	 * @param $sec_lang
	 */
	private function check_date_link_for_language_as_params( $def_lang, $sec_lang ) {
		global $sitepress;
		$this->switch_to_langs_as_params();
		$sitepress->switch_lang( $def_lang );
		$home_url = trailingslashit( home_url() );
		$this->check_links_with_language_negotiation( WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER, $home_url );
		$sitepress->switch_lang( $sec_lang );
		$this->check_links_with_language_negotiation( WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER, $home_url, $sec_lang );
	}

	private function check_links_with_language_negotiation( $lang_negotiation, $home_url, $lang = null ) {
		$year  = mt_rand( 2000, (int) date( 'Y' ) );
		$month = mt_rand( 1, 12 );
		$day   = mt_rand( 1, 28 );

		if ( $lang_negotiation === WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER ) {
			$home_url_with_lang_param = ! isset( $lang ) ? $home_url : $home_url . '?lang=' . $lang;
			$this->switch_to_front( $home_url_with_lang_param );

			$expected_year           = $home_url . '?m=' . $year;
			$expected_year_month     = $expected_year . zeroise( $month, 2 );
			$expected_year_month_day = $expected_year_month . zeroise( $day, 2 );
			if ( isset( $lang ) ) {
				$expected_year           = $expected_year . '&lang=' . $lang;
				$expected_year_month     = $expected_year_month . '&lang=' . $lang;
				$expected_year_month_day = $expected_year_month_day . '&lang=' . $lang;
			}
		} else {
			$this->switch_to_front( $home_url );
			$expected_year           = $home_url . $year;
			$expected_year_month     = $expected_year . '/' . zeroise( $month, 2 );
			$expected_year_month_day = $expected_year_month . '/' . zeroise( $day, 2 );
		}

		$this->assertEquals( $expected_year, get_year_link( $year ) );
		$this->assertEquals( $expected_year_month, get_month_link( $year, $month ) );
		$this->assertEquals( $expected_year_month_day, get_day_link( $year, $month, $day ) );
	}
}
