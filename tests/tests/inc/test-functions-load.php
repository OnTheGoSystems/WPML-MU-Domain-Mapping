<?php

class Test_FunctionsLoad extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1778
	 */
	function test_wpml_reload_active_languages_setting() {
		global $sitepress, $sitepress_settings;

		$active_langs = array_keys( $sitepress->get_active_languages() );
		$setup_helper = wpml_get_setup_instance();
		$setup_helper->set_active_languages( $active_langs );
		sort( $active_langs );
		$active_langs_from_db = wpml_reload_active_languages_setting( true );
		sort( $active_langs_from_db );
		$this->assertEquals( $active_langs, $active_langs_from_db );
		$sitepress_settings = null;
		$this->assertEquals( array(), wpml_reload_active_languages_setting( true ) );
	}
}