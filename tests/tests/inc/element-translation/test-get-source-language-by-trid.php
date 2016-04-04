<?php

class Test_Source_From_Trid extends WPML_UnitTestCase {

	function test_get_source_language_by_trid() {
		global $wpml_post_translations, $sitepress;

		$lang_org             = 'de';
		$lang_trans           = 'fr';
		$post_type_identifier = rand_str( 10 );
		wpml_test_reg_custom_post_type( $post_type_identifier );
		$settings_helper = wpml_load_settings_helper();
		$settings_helper->set_post_type_translatable( $post_type_identifier );
		$post_id_org = wpml_test_insert_post( $lang_org, $post_type_identifier, false, rand_str() );

		$trid = $wpml_post_translations->get_element_trid( $post_id_org );
		$this->assertGreaterThan( 0, $trid );
		wpml_test_insert_post( $lang_trans, $post_type_identifier, $trid, rand_str() );

		$this->assertEquals( 'de', $sitepress->get_source_language_by_trid( $trid ) );
		
		// test that it's cached
		
		$cache = new WPML_WP_Cache( 'get_source_language_by_trid' );
		$found = false;
		$source_language = $cache->get( $trid, $found );
		$this->assertTrue( $found );
		$this->assertEquals( 'de', $source_language );
		
	}
}