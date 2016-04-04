<?php
require_once WPML_TEST_DIR . '/util/wpml-menu-translation-unittestcase.class.php';

class Test_Menu_Sync extends WPML_Menu_Translation_UnitTestCase {
	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1507
	 */
	function test_get_menu_item_translations() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations;

		list( $trid, $menu, $menu_trans ) = $this->create_menu_and_translation();
		$this->assertEquals( $trid, $wpml_term_translations->get_element_trid( $menu_trans->term_taxonomy_id ) );
		$this->assertCount( 1, array_filter( $this->menu_sync->get_menu_translations( $menu->term_id, false ) ) );
		$this->assertCount( 2, array_filter( $this->menu_sync->get_menu_translations( $menu->term_id, true ) ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1851
	 */
	function test_do_sync() {

		$menu_name    = rand_str();
		$menu_id      = wp_create_nav_menu( $menu_name );
		$page_id      = wpml_test_insert_post( 'fr', 'page' );
		$menu_item_id = wp_update_nav_menu_item( $menu_id,
		                                         0,
		                                         array(
			                                         'menu-item-type'      => 'post_type',
			                                         'menu-item-object-id' => $page_id,
			                                         'menu-item-status'    => 'publish',
			                                         'menu-item-object'    => 'page'
		                                         ) );

		$this->assertCount( 1, wp_get_nav_menu_items( $menu_id ) );
		update_post_meta( $menu_item_id, '_menu_item_object_id', '0' );
		$this->assertEquals( array(), $this->menu_sync->do_sync( array() ) );
		$this->assertCount( 0, wp_get_nav_menu_items( $menu_id ) );
	}
}