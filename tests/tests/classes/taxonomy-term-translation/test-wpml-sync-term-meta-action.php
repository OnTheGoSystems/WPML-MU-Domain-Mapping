<?php

class Test_WPML_Sync_Term_Meta_Action extends WPML_UnitTestCase {

	public function test_run() {
		global $sitepress;

		list( $source_lang, $target_lang ) = $this->get_source_and_target_languages( 1 );
		$term            = wpml_test_insert_term( $source_lang, 'category' );
		$translated_term = wpml_test_insert_term( $target_lang, 'category',
			$sitepress->term_translations()->get_element_trid( $term['term_taxonomy_id'] ) );
		$meta_key        = 'foo_meta';
		$meta_value      = rand_str();
		$sitepress->core_tm()->settings_factory()->term_meta_setting( $meta_key )->set_to_copy();
		add_term_meta( $term['term_id'], $meta_key, $meta_value );
		add_term_meta( $translated_term['term_id'], $meta_key,
			$meta_value . '_wrong' );
		$subject = new WPML_Sync_Term_Meta_Action( $sitepress,
			$term['term_taxonomy_id'] );
		$subject->run();
		$this->assertEquals( $meta_value,
			get_term_meta( $translated_term['term_id'], $meta_key, true ) );
	}
}