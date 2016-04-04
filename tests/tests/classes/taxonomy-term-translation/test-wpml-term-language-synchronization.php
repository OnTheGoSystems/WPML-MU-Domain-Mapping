<?php

class Test_WPML_Term_Language_Synchronization extends WPML_UnitTestCase {

	/** @var  string $taxonomy */
	private $taxonomy;

	public function setUp() {
		parent::setUp();
		$this->taxonomy = rand_str();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1972
	 */
	public function test_set_initial_term_language() {
		global $wpdb;

		/** @var WPML_Terms_Translations $term_utils */
		$term_utils = $this->getMockBuilder( 'WPML_Terms_Translations' )->disableOriginalConstructor()->getMock();
		$sitepress  = $this->get_sitepress_mock();
		$def_lang   = 'foo';
		$sitepress->method( 'get_default_language' )->willReturn( $def_lang );
		$sitepress->method( 'wpdb' )->willReturn( $wpdb );
		wpml_test_reg_custom_taxonomy( $this->taxonomy, true, false,
			array( 'cpt' ) );
		$count = rand( 2, 10 );
		$ttids = array();
		for ( $i = 0; $i < $count; $i ++ ) {
			$new_term = wp_insert_term( rand_str( 10 ), $this->taxonomy );
			$ttids[]  = $new_term['term_taxonomy_id'];
		}

		$wpdb->insert( $wpdb->prefix . 'icl_translations',
			array(
				'trid'          => 100,
				'element_type'  => 'tax_' . $this->taxonomy,
				'language_code' => 'de',
				'element_id'    => end( $ttids )
			) );

		$subject = new WPML_Term_Language_Synchronization( $sitepress,
			$term_utils, $this->taxonomy );
		$sitepress->expects( $this->exactly( $count - 1 ) )->method( 'set_element_language_details' )->with( $this->anything() );
		$subject->set_initial_term_language();
	}

	public function test_set_translated() {
		list( $post_id, $correct_ttids, $wrong_ttid ) = $this->check_prepare_missing_originals();
		$this->check_reassign_terms( $post_id, $correct_ttids, $wrong_ttid );
	}

	/**
	 * Checks that the correct original term rows get created in icl_translations
	 * depending on what the existing terms are assigned to already.
	 *
	 * @return array
	 */
	private function check_prepare_missing_originals() {
		global $wpml_post_translations, $sitepress;

		/** @var WPML_Terms_Translations $term_utils */
		list( $source_lang, $sec_lang ) = $this->get_source_and_target_languages( 1 );
		$post_type              = 'post';
		$post_type_untranslated = 'cpt';
		wpml_test_reg_custom_post_type( $post_type_untranslated );
		$untranslated_cpt_id = wpml_test_insert_post( false,
			$post_type_untranslated );
		wpml_test_reg_custom_taxonomy( $this->taxonomy, true, false,
			array( $post_type, $post_type_untranslated ) );
		$new_term                     = wp_insert_term( rand_str( 10 ),
			$this->taxonomy );
		$new_term_loose               = wp_insert_term( rand_str( 10 ),
			$this->taxonomy );
		$new_term_on_untranslated_cpt = wp_insert_term( rand_str( 10 ),
			$this->taxonomy );
		$original_post_id             = wpml_test_insert_post( $source_lang,
			$post_type );
		$translated_post_id           = wpml_test_insert_post( $sec_lang,
			$post_type,
			$wpml_post_translations->get_element_trid( $original_post_id ) );
		$original_post_id_sec_lang    = wpml_test_insert_post( $sec_lang,
			$post_type );
		foreach (
			array(
				$original_post_id,
				$translated_post_id,
				$original_post_id_sec_lang
			) as $post_id
		) {
			wp_set_object_terms( $post_id,
				array( $new_term['term_id'] ),
				$this->taxonomy );
		}
		wp_set_object_terms( $untranslated_cpt_id,
			array( $new_term_on_untranslated_cpt['term_id'] ),
			$this->taxonomy
		);
		$ttid_original = $new_term['term_taxonomy_id'];
		$this->assertNull( $sitepress->term_translations()->get_element_lang_code( $ttid_original ) );
		$this->get_subject()->set_translated();
		$def_lang = $sitepress->get_default_language();
		foreach (
			array(
				$source_lang => $ttid_original,
				$def_lang    => $new_term_loose['term_taxonomy_id'],
				$def_lang    => $new_term_on_untranslated_cpt['term_taxonomy_id']
			) as $lang_code => $ttid_source_lang
		) {
			$this->assertEquals( $lang_code,
				$sitepress->term_translations()->get_element_lang_code( $ttid_source_lang ) );
		}
		$translated_post_terms = array(
			$sitepress->term_translations()->term_id_in( $new_term['term_id'],
				$sec_lang )
		);
		foreach (
			array(
				$translated_post_id,
				$original_post_id_sec_lang
			) as $pid
		) {
			$this->assertEquals( $translated_post_terms,
				wp_get_object_terms( $pid,
					$this->taxonomy, array( 'fields' => 'ids' ) ) );
		}

		return array(
			$translated_post_id,
			$translated_post_terms,
			$ttid_original
		);
	}

	/**
	 * Checks that the functionality can reassign the correct translation of a
	 * term in case the wrong language version of it got assigned to a post by
	 * force.
	 *
	 * @param int   $post_id
	 * @param int[] $correct_ttids
	 * @param int   $wrong_ttid
	 */
	private function check_reassign_terms(
		$post_id,
		$correct_ttids,
		$wrong_ttid
	) {
		global $wpdb;

		$wpdb->update( $wpdb->term_relationships,
			array( 'term_taxonomy_id' => $wrong_ttid ), array(
				'object_id'        => $post_id,
				'term_taxonomy_id' => $correct_ttids[0]
			) );
		$this->get_subject()->set_translated();
		wp_cache_init();
		$this->assertEquals( $correct_ttids,
			wp_get_object_terms( $post_id,
				$this->taxonomy, array( 'fields' => 'ids' ) ) );
	}

	/**
	 * @return WPML_Term_Language_Synchronization
	 */
	private function get_subject() {
		global $sitepress;

		$term_utils = new WPML_Terms_Translations();

		return new WPML_Term_Language_Synchronization( $sitepress,
			$term_utils, $this->taxonomy );
	}
}