<?php
class Test_Set_Translation_Status extends WPML_UnitTestCase {

	function test_post_type_untranslated_icl_object_id() {
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
		$post_id_trans = wpml_test_insert_post( $lang_trans, $post_type_identifier, $trid, rand_str() );

		$this->assertEquals(
			$wpml_post_translations->get_element_trid( $post_id_org ),
			$wpml_post_translations->get_element_trid( $post_id_trans )
		);
		$this->assertEquals( $lang_org, $wpml_post_translations->get_element_lang_code( $post_id_org ) );
		$this->assertEquals( $lang_trans, $wpml_post_translations->get_element_lang_code( $post_id_trans ) );

		$sitepress->switch_lang( $lang_org );
		$this->assertEquals( $post_id_org, icl_object_id( $post_id_org, $post_type_identifier, true ) );

		$sitepress->switch_lang( $lang_trans );
		$this->assertEquals( $post_id_trans, icl_object_id( $post_id_org, $post_type_identifier, true ) );
		$this->assertEquals( $post_id_trans, icl_object_id( $post_id_org, $post_type_identifier, false ) );
		$sitepress->switch_lang( $lang_org );
		$this->assertEquals( $post_id_org, icl_object_id( $post_id_org, $post_type_identifier, true ) );

		$settings_helper->set_post_type_not_translatable( $post_type_identifier );
		$sitepress->switch_lang( $lang_trans );
		$this->assertEquals( $post_id_org, icl_object_id( $post_id_org, $post_type_identifier, true ) );
		$this->assertEquals( $post_id_org, icl_object_id( $post_id_org, $post_type_identifier, false ) );
		_unregister_post_type( $post_type_identifier );
	}

	function test_taxonomy_untranslated_icl_object_id() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations, $sitepress;

		$lang_org   = 'de';
		$lang_trans = 'fr';
		$taxonomy   = rand_str( 10 );
		wpml_test_reg_custom_taxonomy( $taxonomy );
		$settings_helper = wpml_load_settings_helper();
		$settings_helper->set_taxonomy_translatable( $taxonomy );
		$term_org = wpml_test_insert_term( $lang_org, $taxonomy, false, rand_str() );
		$ttid_org = $term_org[ 'term_taxonomy_id' ];
		$trid     = $wpml_term_translations->get_element_trid( $ttid_org );
		$this->assertGreaterThan( 0, $trid );
		$term_trans = wpml_test_insert_term( $lang_trans, $taxonomy, $trid, rand_str() );
		$ttid_trans = $term_trans[ 'term_taxonomy_id' ];

		$this->assertEquals(
			$wpml_term_translations->get_element_trid( $ttid_org ),
			$wpml_term_translations->get_element_trid( $ttid_trans )
		);
		$this->assertEquals( $lang_org, $wpml_term_translations->get_element_lang_code( $ttid_org ) );
		$this->assertEquals( $lang_trans, $wpml_term_translations->get_element_lang_code( $ttid_trans ) );

		$term_id_org   = $term_org[ 'term_id' ];
		$term_id_trans = $term_trans[ 'term_id' ];
		$sitepress->switch_lang( $lang_org );
		$this->assertEquals( $term_id_org, icl_object_id( $term_id_org, $taxonomy, true ) );

		$sitepress->switch_lang( $lang_trans );
		$this->assertEquals( $term_id_trans, icl_object_id( $term_id_org, $taxonomy, true ) );
		$sitepress->switch_lang( $lang_org );
		$this->assertEquals( $term_id_org, icl_object_id( $term_id_org, $taxonomy, true ) );

		$settings_helper->set_taxonomy_not_translatable( $taxonomy );
		$sitepress->switch_lang( $lang_trans );
		$this->assertEquals( $term_id_org, icl_object_id( $term_id_org, $taxonomy, true ) );
		_unregister_taxonomy( $taxonomy );
	}
}