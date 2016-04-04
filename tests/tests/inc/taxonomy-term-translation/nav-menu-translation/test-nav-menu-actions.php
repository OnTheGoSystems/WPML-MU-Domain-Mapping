<?php
require_once ICL_PLUGIN_PATH . '/inc/wp-nav-menus/iclNavMenu.class.php';

class Test_WPML_Nav_Menu_Actions extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1808
	 */
	function test_theme_mod_nav_menu_locations() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations, $sitepress, $wpdb, $wpml_post_translations;

		$subject = new WPML_Nav_Menu_Actions( $sitepress,
		                                      $wpdb,
		                                      $wpml_post_translations,
		                                      $wpml_term_translations );

		foreach ( array( 'dashboard', 'front' ) as $screen ) {
			set_current_screen( $screen );
			$this->assertFalse( $subject->theme_mod_nav_menu_locations( false ) );
			$this->assertEquals( 0, $subject->theme_mod_nav_menu_locations( 0 ) );
		}

		list( $lang_a, $lang_b, $lang_c ) = $this->get_source_and_target_languages( 2 );

		$menus            = array();
		$menu_lang_a      = wpml_test_insert_term( $lang_a, 'nav_menu', false );
		$trid             = $wpml_term_translations->get_element_trid( $menu_lang_a['term_taxonomy_id'] );
		$menus[ $lang_a ] = $menu_lang_a;
		$menus[ $lang_b ] = wpml_test_insert_term( $lang_b, 'nav_menu', $trid );
		$menus[ $lang_c ] = wpml_test_insert_term( $lang_c, 'nav_menu', $trid );

		foreach ( $menus as $lang => $menu_arr ) {
			$menus[ $lang ] = $menu_arr['term_id'];
		}

		set_current_screen( 'dashboard' );
		foreach ( array( $lang_a, $lang_b, $lang_c ) as $lang ) {
			$sitepress->switch_lang( $lang );
			$filtered_menus = $subject->theme_mod_nav_menu_locations( $menus );
			foreach ( array( $lang_a, $lang_b, $lang_c ) as $key ) {
				$this->assertEquals( $menus[ $lang ], $filtered_menus[ $key ] );
			}
		}

		set_current_screen( 'front' );

		foreach ( array( $lang_a, $lang_b, $lang_c ) as $lang ) {
			$sitepress->switch_lang( $lang );
			$filtered_menus = $subject->theme_mod_nav_menu_locations( $menus );
			$this->assertEquals( $menus, $filtered_menus );
		}
	}
}