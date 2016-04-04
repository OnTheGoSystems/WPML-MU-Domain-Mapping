<?php
require_once ICL_PLUGIN_PATH . '/menu/term-taxonomy-menus/wpml-term-language-filter.class.php';

class Test_WPML_Term_Language_Filter extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1931
	 */
	function test_terms_language_filter() {
		$sitepress_mock = $this->get_sitepress_mock();
		$wpdb_mock      = $this->get_wpdb_mock();
		$sitepress_mock->method( 'get_current_language' )->willReturn( 'en' );
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array(
			                                                               'en'  => array( 'display_name' => 'English' ),
			                                                               'foo' => array( 'display_name' => 'Foo\'Bar' )
		                                                               ) );

		$term_lang_filter = new WPML_Term_Language_Filter( $wpdb_mock, $sitepress_mock );
		$html             = $term_lang_filter->terms_language_filter( false );

		$this->assertFalse( strpos( $html, "Foo\'Bar" ) );
	}
}