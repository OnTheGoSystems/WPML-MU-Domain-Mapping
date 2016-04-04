<?php

class Test_Delete_Post extends WPML_UnitTestCase {

	public function setUP() {
		parent::setUp();
		icl_set_setting( 'sync_delete', 1, true );
		remove_all_actions( 'save_post' );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1476
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1479
	 */
	public function test_delete_post_action() {
		global $wpml_post_translations, $wpdb;

		$wpml_post_translations = $this->load_admin_post_actions();
		$this->check_create_delete( $wpml_post_translations, $wpdb, 1 );
		$this->check_create_delete( $wpml_post_translations, $wpdb, 2 );
		$this->check_create_delete( $wpml_post_translations, $wpdb, 3 );
		$this->check_create_delete( $wpml_post_translations, $wpdb, 4 );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2235
	 */
	public function test_delete_page_child() {
		$primary_lang = 'en';
		$sec_lang     = 'de';
		icl_set_setting( 'sync_delete', 0, true );
		$wpml_post_translations = $this->load_admin_post_actions();
		list( , $child_orig, , $child_trans ) = $this->setup_two_nested_pages( $sec_lang );
		$this->assertEquals( $primary_lang, $wpml_post_translations->get_source_lang_code( $child_trans ) );
		$trid_child = $wpml_post_translations->get_element_trid( $child_trans );
		wp_delete_post( $child_orig, true );
		$wpml_post_translations->reload();
		wp_cache_flush();
		$this->assertFalse( (bool) $wpml_post_translations->get_source_lang_code( $child_trans ) );
		$this->assertEquals( $trid_child, $wpml_post_translations->get_element_trid( $child_trans ) );
		$child_recreated = wpml_test_insert_post( $primary_lang, 'page', $trid_child, rand_str() );
		$this->assertFalse( (bool) $wpml_post_translations->get_source_lang_code( $child_trans ) );
		$this->assertEquals( $sec_lang, $wpml_post_translations->get_source_lang_code( $child_recreated ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1524
	 */
	function test_delete_hierarchy_sync() {
		list( $parent_orig, $child_orig, , $child_trans ) = $this->setup_two_nested_pages();
		wp_delete_post( $parent_orig, true );
		$this->assertFalse( (bool) wp_get_post_parent_id( $child_orig ) );
		$this->assertFalse( (bool) wp_get_post_parent_id( $child_trans ) );
	}

	private function setup_two_nested_pages( $sec_lang = 'de' ) {
		global $sitepress_settings, $wpml_post_translations, $wpdb;

		remove_all_actions( 'save_post' );
		icl_set_setting( 'sync_page_parent', 1, true );
		$sitepress_settings['sync_page_parent'] = 1;
		$def_lang                               = $sitepress_settings['default_language'];
		$wpml_post_translations                 = new WPML_Admin_Post_Actions( $sitepress_settings, $wpdb );
		$parent_orig                            = wpml_test_insert_post( $def_lang, 'page', false, rand_str() );
		$child_orig                             = wpml_test_insert_post( $def_lang,
			'page',
			false,
			rand_str(),
			$parent_orig );
		$trid_parent                            = $wpml_post_translations->get_element_trid( $parent_orig );
		$trid_child                             = $wpml_post_translations->get_element_trid( $child_orig );
		$parent_trans                           = wpml_test_insert_post( $sec_lang,
			'page',
			$trid_parent,
			rand_str() );
		$child_trans                            = wpml_test_insert_post( $sec_lang,
			'page',
			$trid_child,
			rand_str(),
			$parent_trans );
		$wpml_post_translations->init();
		$this->assertEquals( $parent_trans, wp_get_post_parent_id( $child_trans ) );

		return array( $parent_orig, $child_orig, $parent_trans, $child_trans );
	}

	/**
	 *
	 * @param WPML_Post_Translation $wpml_post_translations
	 * @param wpdb                  $wpdb
	 * @param int                   $force_delete
	 */
	private function check_create_delete( $wpml_post_translations, $wpdb, $force_delete ) {

		$post_orig         = wpml_test_insert_post( 'en', 'post', false, rand_str() );
		$trid              = $wpml_post_translations->get_element_trid( $post_orig );
		$post_trans        = wpml_test_insert_post( 'de', 'post', $trid, rand_str() );
		$post_sec_trans    = wpml_test_insert_post( 'fr', 'post', $trid, rand_str() );
		$post_trans_fourth = wpml_test_insert_post( 'ru', 'post', $trid, rand_str() );
		$affected_ids_arr  = array( $post_orig, $post_trans, $post_sec_trans, $post_trans_fourth );
		$wpdb->update(
			$wpdb->prefix . 'icl_translations',
			array( 'source_language_code' => 'fr' ),
			array(
				'element_type' => 'post_post',
				'element_id'   => $post_trans_fourth
			)
		);
		$this->assertEquals( $trid, $wpml_post_translations->get_element_trid( $post_trans ) );
		$this->assertEquals( $trid, $wpml_post_translations->get_element_trid( $post_sec_trans ) );
		$this->assertEquals( $trid, $wpml_post_translations->get_element_trid( $post_trans_fourth ) );
		$this->assertEquals( 'fr', $wpml_post_translations->get_source_lang_code( $post_trans_fourth ) );

		$wpml_post_translations->init();

		wp_trash_post( $post_orig );
		$this->check_all_in_status( $affected_ids_arr, 'trash' );

		if ( $force_delete === 1 ) {
			wp_untrash_post( $post_orig );
			$this->check_all_in_status( $affected_ids_arr, 'publish' );
			wp_delete_post( $post_orig, true );
		} elseif ( $force_delete === 2 ) {
			wp_delete_post( $post_orig );
		} elseif ( $force_delete === 3 ) {
			$_GET[ 'delete_all' ] = 'Empty Trash';
			wp_delete_post( $post_orig );
			$this->assertNotNull( get_post( $post_trans ) );
			$this->assertNotNull( get_post( $post_sec_trans ) );
			$this->assertNotNull( get_post( $post_trans_fourth ) );
			unset( $_GET[ 'delete_all' ] );
		} elseif ( $force_delete === 4 ) {
			$_GET[ 'ids' ] = array( $post_trans );
			wp_delete_post( $post_orig );
			$this->assertNotNull( get_post( $post_trans ) );
			$this->check_all_deleted( array( $post_sec_trans, $post_trans_fourth ) );
			unset( $_GET[ 'ids' ] );
		}

		if ( $force_delete !== 3 && $force_delete !== 4 ) {
			$this->check_all_deleted( $affected_ids_arr );
		}
	}

	private function check_all_in_status( $post_ids, $status ) {
		foreach ( $post_ids as $post_id ) {
			$this->assertEquals( $status, get_post_status( $post_id ) );
		}
	}

	private function check_all_deleted( $post_ids ) {
		foreach ( $post_ids as $post_id ) {
			$this->assertNull( get_post( $post_id ) );
		}
	}

	private function load_admin_post_actions() {
		global $wpml_post_translations, $sitepress_settings, $wpdb, $wpml_request_handler;

		$wpml_request_handler   = wpml_load_request_handler( true, $sitepress_settings['active_languages'], $sitepress_settings['default_language'] );
		$wpml_post_translations = new WPML_Admin_Post_Actions( $sitepress_settings, $wpdb );

		return $wpml_post_translations;
	}

	function tearDown() {
		global $sitepress_settings;

		remove_all_actions( 'save_post' );
		remove_all_actions( 'wp_trash_post' );
		remove_all_actions( 'delete_post' );
		remove_all_actions( 'untrashed_post' );
		_delete_all_posts();
		wpml_load_request_handler(
			false,
			wpml_reload_active_languages_setting(),
			$sitepress_settings[ 'default_language' ]
		);
	}
}