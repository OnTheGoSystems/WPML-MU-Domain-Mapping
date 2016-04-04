<?php

class Test_AbsoluteLinks extends WPML_UnitTestCase {

	function setUp() {
		/** @var WP_Rewrite $wp_rewrite */
		global $wp_rewrite;
		parent::setUp();
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( "%postname%" );
		create_initial_taxonomies();
		$wp_rewrite->flush_rules();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1530
	 */
	function test_process_generic_text() {
		global $sitepress, $sitepress_settings;

		icl_set_setting( 'language_negotiation_type', 1, true );
		$sitepress_settings[ 'urls' ][ 'directory_for_default_language' ] = 0;
		icl_set_setting( 'urls', $sitepress_settings[ 'urls' ], true );
		$converter       = load_wpml_url_converter(
			$sitepress_settings,
			1,
			$sitepress_settings['default_language']
		);
		$def_lang        = $sitepress_settings[ 'default_language' ];
		$post_name       = rand_str();
		$post_id_correct = wpml_test_insert_post( $def_lang,
												  'post',
												  false,
												  $post_name );
		$abs_home        = $converter->get_abs_home();
		$post_url        = trailingslashit( $abs_home ) . sanitize_title( $post_name );

		$post_id = $sitepress->url_to_postid( $post_url );
		$this->assertEquals( '/?p=' . $post_id_correct, $post_id );
	}
}
