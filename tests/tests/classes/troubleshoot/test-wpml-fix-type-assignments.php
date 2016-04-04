<?php

class Test_WPML_Fix_Type_Assignments extends WPML_UnitTestCase {

	function test_run() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress, $wpdb, $wpml_post_translations;

		list( $original_lang, $translated_lang, $translated_lang_sec ) = $this->get_source_and_target_languages( 2 );
		$original_id       = wpml_test_insert_post( $original_lang, 'post',
			false, rand_str() );
		$translated_id     = wpml_test_insert_post( $translated_lang, 'post',
			false, rand_str() );
		$trid              = $wpml_post_translations->get_element_trid( $original_id );
		$translated_id_sec = wpml_test_insert_post( $translated_lang_sec,
			'post', $trid, rand_str() );
		$sitepress->set_element_language_details( $translated_id, 'post_post',
			$trid, $translated_lang );
		$wpml_post_translations->reload();
		foreach ( array( $translated_id, $translated_id_sec ) as $el_id ) {
			$this->assertEquals( $trid,
				$wpml_post_translations->get_element_trid( $el_id ) );
		}
		$wpdb->update(
			$wpdb->prefix . 'icl_translations',
			array( 'source_language_code' => $original_lang ),
			array(
				'element_id' => $original_id,
				'trid'       => $wpml_post_translations->get_element_trid( $original_id )
			)
		);
		$wpml_post_translations->reload();
		foreach ( array( $translated_id, $original_id ) as $el_id ) {
			$this->assertEquals( $original_lang,
				$wpml_post_translations->get_source_lang_code( $el_id ) );
		}
		$subject = new WPML_Fix_Type_Assignments( $sitepress );
		$this->assertEquals( 1, $subject->run() );
		$wpml_post_translations->reload();

		$this->assertEquals( $original_lang,
			$wpml_post_translations->get_source_lang_code( $translated_id ) );
		$this->assertFalse( (bool) $wpml_post_translations->get_source_lang_code( $original_id ) );

		$this->check_repair_post_type_assignment( $trid, $original_id,
			$translated_id, $translated_id_sec, $subject );
		$this->check_repair_taxonomy_type_assignment( $subject );
		$this->check_repair_duplicate_removal( $subject );
	}

	/**
	 * @param                           $trid
	 * @param                           $original_id
	 * @param                           $translated_id
	 * @param                           $translated_id_sec
	 * @param WPML_Fix_Type_Assignments $subject
	 */
	private function check_repair_post_type_assignment(
		$trid,
		$original_id,
		$translated_id,
		$translated_id_sec,
		$subject
	) {
		global $wpdb;

		$changed_type     = 'page';
		$changed_icl_type = 'post_' . $changed_type;
		$ids              = array(
			$original_id,
			$translated_id,
			$translated_id_sec
		);
		$wpdb->update(
			$wpdb->prefix . 'icl_translations',
			array( 'element_type' => $changed_icl_type ),
			array(
				'trid'       => $trid,
				'element_id' => $original_id
			)
		);
		foreach ( $ids as $id ) {
			$wpdb->update( $wpdb->posts, array( 'post_type' => $changed_type ),
				array( 'ID' => $id ) );
		}
		wp_cache_init();
		foreach (
			array(
				$translated_id     => 'post_post',
				$translated_id_sec => 'post_post',
				$original_id       => $changed_icl_type
			) as $id => $el_type
		) {
			$this->assertEquals( $el_type, $this->get_icl_post_type( $id ) );
		}
		$fixed_rows = $subject->run();
		$this->assertEquals( 2, $fixed_rows );
		icl_cache_clear();
		wp_cache_init();

		foreach ( $ids as $id ) {
			$this->assertEquals( $changed_icl_type,
				$this->get_icl_post_type( $id ) );
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlsupp-2659
	 *
	 * @param WPML_Fix_Type_Assignments $subject
	 */
	private function check_repair_duplicate_removal( $subject ) {
		global $wpdb;

		$post_id = wpml_test_insert_post( 'en', 'post' );
		$wpdb->insert( $wpdb->prefix . 'icl_translations', array(
			'element_type'  => 'post_foo',
			'element_id'    => $post_id,
			'language_code' => 'en'
		) );
		$subject->run();
		$this->assertEquals( 0,
			(int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}icl_translations WHERE element_type = 'post_foo'" ) );
	}

	/**
	 * @param WPML_Fix_Type_Assignments $subject
	 */
	private function check_repair_taxonomy_type_assignment( $subject ) {
		global $wpdb;

		$term = wpml_test_insert_term( 'en' );
		$ttid = $term['term_taxonomy_id'];
		$this->assertEquals(
			'tax_' . $this->taxonomy_from_tt_table( $ttid ),
			$this->get_icl_taxonomy_type( $ttid )
		);
		$wpdb->update( $wpdb->term_taxonomy, array( 'taxonomy' => 'fooo' ),
			array( 'term_taxonomy_id' => $ttid ) );
		$this->assertNotEquals(
			'tax_' . $this->taxonomy_from_tt_table( $ttid ),
			$this->get_icl_taxonomy_type( $ttid )
		);
		$subject->run();
		$this->assertEquals(
			'tax_' . $this->taxonomy_from_tt_table( $ttid ),
			$this->get_icl_taxonomy_type( $ttid )
		);
	}

	private function taxonomy_from_tt_table( $ttid ) {
		global $wpdb;

		return $wpdb->get_var( "SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id = " . $ttid );
	}

	/**
	 * @param int $term_taxonomy_id
	 *
	 * @return null|string
	 */
	private function get_icl_taxonomy_type( $term_taxonomy_id ) {

		return $this->get_icl_type( $term_taxonomy_id, 'tax' );
	}

	/**
	 * @param int $post_id
	 *
	 * @return null|string
	 */
	private function get_icl_post_type( $post_id ) {

		return $this->get_icl_type( $post_id, 'post' );
	}

	/**
	 * @param int    $id
	 * @param string $prefix either 'tax' or 'post'
	 *
	 * @return null|string
	 */
	private function get_icl_type( $id, $prefix ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT element_type
												FROM {$wpdb->prefix}icl_translations
												WHERE element_id = %d
													AND element_type LIKE '{$prefix}%%'
												LIMIT 1",
			$id ) );
	}
}