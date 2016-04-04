<?php

class Test_WPML_Post_Translation extends WPML_UnitTestCase {

	public function test_get_element_translations() {
		global $wpml_post_translations;

		$def_lang = wpml_get_setting_filter( false, 'default_language' );
		$orig     = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );
		$trid     = $wpml_post_translations->get_element_trid( $orig );

		$translations = $wpml_post_translations->get_element_translations( false, $trid );
		$this->assertCount( 1, $translations );
		$this->assertArrayHasKey( $def_lang, $translations );
		$this->assertEquals( $orig, $translations[ $def_lang ] );
		$translations = $wpml_post_translations->get_element_translations( $orig );
		$this->assertCount( 1, $translations );
		$this->assertArrayHasKey( $def_lang, $translations );
		$this->assertEquals( $orig, $translations[ $def_lang ] );

		$sec_lang   = 'fr';
		$third_lang = 'de';
		$second     = wpml_test_insert_post( $sec_lang, 'post', $trid, rand_str() );
		$third      = wpml_test_insert_post( $third_lang, 'post', $trid, rand_str() );

		$translations = $wpml_post_translations->get_element_translations( false, $trid );
		$this->assertCount( 3, $translations );
		$this->assertArrayHasKey( $def_lang, $translations );
		$this->assertArrayHasKey( $sec_lang, $translations );
		$this->assertArrayHasKey( $third_lang, $translations );
		$this->assertEquals( $orig, $translations[ $def_lang ] );
		$this->assertEquals( $second, $translations[ $sec_lang ] );
		$this->assertEquals( $third, $translations[ $third_lang ] );
		$translations = $wpml_post_translations->get_element_translations( $orig );
		$this->assertCount( 3, $translations );
		$this->assertArrayHasKey( $def_lang, $translations );
		$this->assertArrayHasKey( $sec_lang, $translations );
		$this->assertArrayHasKey( $third_lang, $translations );
		$this->assertEquals( $orig, $translations[ $def_lang ] );
		$this->assertEquals( $second, $translations[ $sec_lang ] );
		$this->assertEquals( $third, $translations[ $third_lang ] );

		$translations = $wpml_post_translations->get_element_translations( $orig, false, true );
		$this->assertCount( 2, $translations );
		$this->assertArrayNotHasKey( $def_lang, $translations );
		$this->assertArrayHasKey( $sec_lang, $translations );
		$this->assertArrayHasKey( $third_lang, $translations );
		$this->assertEquals( $second, $translations[ $sec_lang ] );
		$this->assertEquals( $third, $translations[ $third_lang ] );
	}

	public function test_get_original_id() {
		global $wpml_post_translations, $wpdb;

		list( $original_lang, $sec_lang, $third_lang ) = $this->get_source_and_target_languages( 2 );
		$post_type     = 'post';
		$original_id   = wpml_test_insert_post( $original_lang, $post_type );
		$trid          = $wpml_post_translations->get_element_trid( $original_id );
		$sec_post_id   = wpml_test_insert_post( $sec_lang, $post_type, $trid );
		$third_post_id = wpml_test_insert_post( $third_lang, $post_type, $trid );
		$wpdb->update( $wpdb->prefix . 'icl_translations', array( 'source_language_code' => $sec_lang ), array(
			'element_id'   => $third_post_id,
			'element_type' => 'post_post'
		) );
		$wpml_post_translations->reload();
		$this->assertNull( $wpml_post_translations->get_source_lang_code( $original_id ) );
		$this->assertNull( $wpml_post_translations->get_original_element( $original_id ) );
		$this->assertNull( $wpml_post_translations->get_original_element( $original_id, true ) );
		$this->assertEquals( $original_lang, $wpml_post_translations->get_source_lang_code( $sec_post_id ) );
		$this->assertEquals( $original_id, $wpml_post_translations->get_original_element( $sec_post_id, true ) );
		$this->assertEquals( $sec_lang, $wpml_post_translations->get_source_lang_code( $third_post_id ) );
		$this->assertEquals( $original_id, $wpml_post_translations->get_original_element( $third_post_id, true ) );
	}

	public function test_get_element_language_details() {
		global $wpml_post_translations;

		$lang                         = 'en';
		$post_id                      = wpml_test_insert_post( $lang, 'post' );
		$element_language_details_obj = $wpml_post_translations->get_element_language_details( $post_id );
		$this->assertInstanceOf( 'stdClass', $element_language_details_obj );
		$this->assertTrue( isset( $element_language_details_obj->element_id ) );
		$this->assertEquals( $post_id, $element_language_details_obj->element_id );

		$element_language_details_a_array = $wpml_post_translations->get_element_language_details( $post_id, ARRAY_A );
		$this->assertInternalType( 'array', $element_language_details_a_array );
		$this->assertTrue( isset( $element_language_details_a_array['element_id'] ) );
		$this->assertEquals( $post_id, $element_language_details_a_array['element_id'] );

		$element_language_details_n_array = $wpml_post_translations->get_element_language_details( $post_id, ARRAY_N );
		$this->assertInternalType( 'array', $element_language_details_n_array );
		$this->assertTrue( isset( $element_language_details_n_array[0] ) );
		$this->assertEquals( $post_id, $element_language_details_n_array[0] );
	}
}