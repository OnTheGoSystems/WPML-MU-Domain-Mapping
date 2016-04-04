<?php

class Test_Set_Element_Language extends WPML_UnitTestCase {

	public function test_set_same_lang_twice() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress, $wpml_term_translations;

		$translated_language = 'de';
		$tag                 = wp_insert_term( 'test', 'post_tag' );
		$ttid                = $tag[ 'term_taxonomy_id' ];

		$default_language = $sitepress->get_default_language();
		$sitepress->set_element_language_details( $ttid, 'tax_post_tag', false, $sitepress->get_default_language() );
		$lang = $wpml_term_translations->get_element_lang_code( $ttid );
		$trid = $wpml_term_translations->get_element_trid( $ttid );

		$this->assertEquals( $default_language, $lang );
		$this->assertTrue( (bool) $trid );

		$sitepress->set_element_language_details( $ttid, 'tax_post_tag', $trid, $default_language );
		$wpml_term_translations->reload();

		$lang_new = $wpml_term_translations->get_element_lang_code( $ttid );
		$trid_new = $wpml_term_translations->get_element_trid( $ttid );

		$this->assertEquals( $lang, $lang_new );
		$this->assertEquals( $trid, $trid_new );

		$tag_translated  = wp_insert_term( 'test_translated', 'post_tag' );
		$ttid_translated = $tag_translated[ 'term_taxonomy_id' ];
		$sitepress->set_element_language_details( $ttid_translated, 'tax_post_tag', $trid, $translated_language );

		$this->assertEquals( $translated_language, $wpml_term_translations->get_element_lang_code( $ttid_translated ) );
		$this->assertEquals( $trid, $wpml_term_translations->get_element_trid( $ttid_translated ) );
		$this->assertEquals( $default_language, $wpml_term_translations->get_source_lang_code( $ttid_translated ) );

		$sitepress->set_element_language_details( $ttid_translated, 'tax_post_tag', false, $translated_language );

		$this->assertNotEquals( $trid, $wpml_term_translations->get_element_trid( $ttid_translated ) );
		$this->assertFalse( (bool) $wpml_term_translations->get_source_lang_code( $ttid_translated ) );
	}

	public function test_orphan_removal() {
		global $wpdb, $wpml_post_translations;

		$sec_lang     = 'de';
		$wrong_source = 'fr';
		$orig         = wpml_test_insert_post( 'en' );
		$trid         = $wpml_post_translations->get_element_trid( $orig );
		$wpdb->insert( $wpdb->prefix . 'icl_translations',
					   array(
						   'trid'                 => $trid,
						   'element_id'           => 1000,
						   'element_type'         => 'post_post',
						   'source_language_code' => 'fr',
						   'language_code'        => $sec_lang
					   ) );
		$trans = wpml_test_insert_post( $sec_lang, 'post', $trid );
		$el_id = $wpdb->get_var( $wpdb->prepare( "	SELECT element_id
													FROM {$wpdb->prefix}icl_translations
													WHERE trid=%d
														AND language_code = %s",
												 $trid,
												 $sec_lang ) );
		$this->assertEquals( $trans, $el_id );
		$this->assertNull( $wpdb->get_var( $wpdb->prepare( "SELECT element_id
															FROM {$wpdb->prefix}icl_translations
															WHERE source_language_code = %s ",
														   $wrong_source ),
										   $wrong_source ) );
	}

	public function test_basics() {
		global $wpml_post_translations;

		$trid = rand( 1000, 10000 );
		$lang = 'fr';

		$post_id = wpml_test_insert_post( $lang, 'page', $trid, 'rootaroo' );

		$this->assertEquals( $post_id, $wpml_post_translations->get_element_id( $lang, $trid ) );
	}

	public function test_save_placeholder() {
		global $sitepress, $wpml_post_translations;

		$post_id        = wpml_test_insert_post( 'en' );
		$trid           = $wpml_post_translations->get_element_trid( $post_id );
		$translation_id = $sitepress->set_element_language_details( null, 'post_post', $trid, 'fr', 'en' );
		wpml_test_insert_post( 'en' );
		$translation_id_updated = $sitepress->set_element_language_details( null, 'post_post', $trid, 'fr', 'en' );

		$this->assertGreaterThan( 1, $translation_id );
		$this->assertEquals( $translation_id, $translation_id_updated );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_update_on_duplicate() {
		global $sitepress;

		$post_id = wpml_test_insert_post( 'en' );
		$sitepress->set_element_language_details( $post_id, 'post_post_type_that_doesnt_exist', false, 'en' );
	}

	public function test_set() {
		global $sitepress;

		$term = wpml_test_insert_term( 'en', 'category' );
		$wpdb = $sitepress->wpdb();
		$wpdb->update( $wpdb->prefix . 'icl_translations',
			array( 'element_type' => 'tax_not_a_category' ),
			array(
				'element_type' => 'tax_category',
				'element_id'   => $term['term_taxonomy_id']
			) );
		$sitepress->set_element_language_details( $term['term_taxonomy_id'],
			'tax_category', false, 'en' );
		$this->assertEquals(
			'tax_category',
			$wpdb->get_var(
				"SELECT element_type FROM {$wpdb->prefix}icl_translations
				 WHERE element_id = " . $term['term_taxonomy_id']
				. " AND element_type LIKE 'tax%'" ) );
	}

	public function test_root_page_lang_removal() {
		global $wpml_post_translations;

		$root_id = wpml_test_insert_post( 'fr', 'page', false, 'rootaroo' );

		$this->assertEquals( 'fr', $wpml_post_translations->get_element_lang_code( $root_id ) );

		$urls                                     = icl_get_setting( 'urls' );
		$urls[ 'root_page' ]                      = $root_id;
		$urls[ 'directory_for_default_language' ] = 1;
		$urls[ 'show_on_root' ]                   = 'page';

		icl_set_setting( 'language_negotiation_type', 1, true );
		icl_set_setting( 'urls', $urls, true );
		$root_page_actions = wpml_get_root_page_actions_obj();
		add_action( 'icl_set_element_language', array( $root_page_actions, 'delete_root_page_lang' ), 10, 0 );

		$other_id = wpml_test_insert_post( 'en', 'page', false, 'someontherpost' );

		$this->assertEquals( 'en', $wpml_post_translations->get_element_lang_code( $other_id ) );
		$this->assertFalse( (bool) $wpml_post_translations->get_element_lang_code( $root_id ) );
		$wpml_post_translations->reload();
		$this->assertFalse( (bool) $wpml_post_translations->get_element_lang_code( $root_id ) );
		$this->assertEquals( 'en', $wpml_post_translations->get_element_lang_code( $other_id ) );
	}

	public function test_change_language() {
		global $wpml_post_translations, $sitepress, $wpdb;

		$post_id                         = wpml_test_insert_post( 'en' );
		$trid                            = $wpml_post_translations->get_element_trid( $post_id );
		$translation_id                  = $wpdb->get_var( $wpdb->prepare( "	SELECT translation_id
															FROM {$wpdb->prefix}icl_translations
															WHERE trid = %d
															LIMIT 1",
		                                                                   $trid ) );
		$translation_id_update_no_change = $sitepress->set_element_language_details( $post_id,
		                                                                             'post_post',
		                                                                             $trid,
		                                                                             'en' );
		$this->assertEquals( $translation_id, $translation_id_update_no_change );
		$translation_id_update_change_lang = $sitepress->set_element_language_details( $post_id,
		                                                                               'post_post',
		                                                                               $trid,
		                                                                               'fr' );
		$this->assertEquals( $translation_id, $translation_id_update_change_lang );
	}
}
