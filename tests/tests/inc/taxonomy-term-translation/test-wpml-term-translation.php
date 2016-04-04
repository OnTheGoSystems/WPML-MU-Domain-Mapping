<?php

class Test_WPML_Term_Translation extends WPML_UnitTestCase {

	function test_lang_code_by_termid() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress, $wpml_term_translations, $wpdb;

		$menu_term = wp_create_term( 'test_tag', 'post_tag' );
		$menu_ttid = isset( $menu_term[ 'term_taxonomy_id' ] ) ? $menu_term[ 'term_taxonomy_id' ] : false;
		$this->assertTrue( (bool) $menu_ttid, 'Menu Term could no be created!' );
		$sitepress->set_element_language_details( $menu_ttid, 'tax_post_tag', false, 'en', null );
		$this->assertEquals( 'en', $wpml_term_translations->lang_code_by_termid( $menu_term[ 'term_id' ] ) );
		$this->assertFalse( (bool) $wpml_term_translations->lang_code_by_termid( $menu_term[ 'term_id' ] + 1 ) );

		$wpdb->update( $wpdb->term_taxonomy,
					   array( 'term_id' => ( $menu_term[ 'term_taxonomy_id' ] + 1 ) ),
					   array( 'term_taxonomy_id' => $menu_term[ 'term_taxonomy_id' ] ) );
		$wpdb->update( $wpdb->terms,
					   array( 'term_id' => ( $menu_term[ 'term_taxonomy_id' ] + 1 ) ),
					   array( 'term_id' => $menu_term[ 'term_id' ] ) );

		icl_cache_clear();
		wp_cache_init();

		$this->assertEquals( 'en', $wpml_term_translations->lang_code_by_termid( $menu_term[ 'term_id' ] + 1 ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1641
	 */
	function test_create_new_term() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpdb, $wpml_term_translations;

		$lang = 'de';
		$name = rand_str( 7 );
		$slug = $name . 'slug';

		$args = array(
			'taxonomy'  => 'category',
			'lang_code' => $lang,
			'term'      => $name,
			'trid'      => false,
			'slug'      => $slug,
			'overwrite' => true
		);

		$term          = WPML_Terms_Translations::create_new_term( $args );
		$term_slug_sql = $wpdb->prepare( "SELECT slug FROM {$wpdb->terms} WHERE term_id = %d LIMIT 1",
										 $term[ 'term_id' ] );
		$this->assertEquals( $slug, $wpdb->get_var( $term_slug_sql ) );
		$trid = $wpml_term_translations->get_element_trid( $term[ 'term_taxonomy_id' ] );
		unset( $args[ 'slug' ] );
		$args[ 'trid' ] = $trid;
		WPML_Terms_Translations::create_new_term( $args );
		$this->assertEquals( $name, $wpdb->get_var( $term_slug_sql ) );
	}
}