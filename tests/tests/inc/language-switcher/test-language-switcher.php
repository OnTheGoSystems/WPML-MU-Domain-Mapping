<?php

class Test_SitePressLanguageSwitcher extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1645
	 */
	function test_wp_nav_menu_items_filter() {
		global $sitepress, $wpml_language_resolution;

		$this->switch_to_langs_in_domains();
		$def_lang = $sitepress->get_default_language();
		$sec_lang = 'fr';

		$locations = array( 'top' => null, 'middle' => null, 'bottom' => null );
		set_theme_mod( 'nav_menu_locations', $locations );

		foreach ( array( $def_lang, $sec_lang ) as $language_code ) {
			$menu = wpml_test_insert_term( $language_code, 'nav_menu' );

			$this->assertNotEmpty( $locations );
			foreach ( $locations as $key => $location ) {
				$locations[ $key ] = $menu[ 'term_id' ];
			}
			icl_set_setting( 'menu_for_ls', $menu[ 'term_id' ], true );
			icl_set_setting( 'display_ls_in_menu', true, true );

			$args              = new stdClass();
			$args->menu        = $menu[ 'term_id' ];
			$language_switcher = new SitePressLanguageSwitcher();
			$active_lang_codes = $wpml_language_resolution->get_active_language_codes();

			foreach ( $active_lang_codes as $lang ) {
				$sitepress->switch_lang( $lang );
				foreach ( $locations as $loc => $menu_id ) {
					$args->theme_location = $loc;
					$this->assertTrue( (bool) $language_switcher->wp_nav_menu_items_filter( '', $args ) );
				}
			}
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2734
	 */
	function test_language_selector_widget_init() {
		global $wp_registered_widgets;

		icl_set_setting( 'setup_complete', 0, true );
		$language_switcher = new SitePressLanguageSwitcher();
		$language_switcher->init();
		$language_switcher->language_selector_widget_init();
		do_action( 'widgets_init' );
		$this->assertFalse( isset( $wp_registered_widgets['icl_lang_sel_widget-1'] ) );

		icl_set_setting( 'setup_complete', 1, true );
		$language_switcher = new SitePressLanguageSwitcher();
		$language_switcher->init();
		$language_switcher->language_selector_widget_init();
		do_action( 'widgets_init' );
		$this->assertTrue( isset( $wp_registered_widgets['icl_lang_sel_widget-1'] ) );
	}
}