<?php

class Test_WPML_Taxonomy_Translation_Screen extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1478
	 */
	function test_term_retrieval() {
		global $sitepress;

		$taxonomy = 'category';
		$this->check_term_retrieval( $taxonomy );
		wpml_test_insert_term( 'de', $taxonomy, false, rand_str() );
		$terms_data = new WPML_Taxonomy_Translation_Screen_Data( $sitepress,
			$taxonomy );
		$this->assertCount( 3, $terms_data->terms() );
	}

	private function check_term_retrieval( $taxonomy ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations, $sitepress;

		$def_lang    = wpml_get_setting_filter( false, 'default_language' );
		$sec_lang    = 'de';
		$orig_term_a = wpml_test_insert_term( $def_lang, $taxonomy, false, rand_str() );
		$terms_data = new WPML_Taxonomy_Translation_Screen_Data( $sitepress,
			$taxonomy );
		$this->assertCount( 1, $terms_data->terms() );

		$trid_a       = $wpml_term_translations->get_element_trid( $orig_term_a[ 'term_taxonomy_id' ] );
		$orig_term_b  = wpml_test_insert_term( $def_lang, $taxonomy, false, rand_str(), $orig_term_a[ 'term_id' ] );
		$trid_b       = $wpml_term_translations->get_element_trid( $orig_term_b[ 'term_taxonomy_id' ] );
		$trans_term_a = wpml_test_insert_term( $sec_lang, $taxonomy, $trid_b, rand_str() );
		$trans_term_b = wpml_test_insert_term( $sec_lang, $taxonomy, $trid_a, rand_str(), $trans_term_a[ 'term_id' ] );

		$this->assertEquals(
			$wpml_term_translations->get_element_trid( $orig_term_b[ 'term_taxonomy_id' ] ),
			$wpml_term_translations->get_element_trid( $trans_term_a[ 'term_taxonomy_id' ] )
		);

		$this->assertEquals(
			$wpml_term_translations->get_element_trid( $trans_term_b[ 'term_taxonomy_id' ] ),
			$wpml_term_translations->get_element_trid( $orig_term_a[ 'term_taxonomy_id' ] )
		);

		$this->assertEquals(
			$def_lang,
			$wpml_term_translations->get_element_lang_code( $orig_term_a[ 'term_taxonomy_id' ] )
		);
		$this->assertEquals(
			$def_lang,
			$wpml_term_translations->get_element_lang_code( $orig_term_b[ 'term_taxonomy_id' ] )
		);
		$this->assertEquals(
			$sec_lang,
			$wpml_term_translations->get_element_lang_code( $trans_term_a[ 'term_taxonomy_id' ] )
		);
		$this->assertEquals(
			$sec_lang,
			$wpml_term_translations->get_element_lang_code( $trans_term_b[ 'term_taxonomy_id' ] )
		);
		$terms_data = new WPML_Taxonomy_Translation_Screen_Data( $sitepress,
			$taxonomy );
		$this->assertCount( 2, $terms_data->terms() );

		return array( 't_a' => $trans_term_a, 't_b' => $trans_term_b, 'a' => $orig_term_a, 'b' => $orig_term_b );
	}

	function test_is_need_sync() {
		$taxonomy = 'category';
		$def_lang = wpml_get_setting_filter( false, 'default_language' );
		$sec_lang = 'de';

		$terms        = $this->check_term_retrieval( $taxonomy );
		$hiera_helper = wpml_get_hierarchy_sync_helper( 'term' );
		$this->assertTrue( $hiera_helper->is_need_sync( $taxonomy, $def_lang ) );
		$unsynced = $hiera_helper->get_unsynced_elements( $taxonomy, $def_lang );
		$this->assertCount( 2, $unsynced );

		$found_a_sync = false;
		$found_b_sync = false;

		foreach ( $unsynced as $changes ) {
			if ( $changes->translated_id == $terms[ 't_b' ][ 'term_taxonomy_id' ] && !$changes->correct_parent ) {
				$found_a_sync = true;
			}
			if ( $changes->translated_id == $terms[ 't_a' ][ 'term_taxonomy_id' ] && $terms[ 't_b' ][ 'term_id' ] == $changes->correct_parent ) {
				$found_b_sync = true;
			}
		}

		$this->assertTrue( $found_a_sync );
		$this->assertTrue( $found_b_sync );

		$this->assertTrue( $hiera_helper->is_need_sync( $taxonomy, $sec_lang ) );
		$unsynced = $hiera_helper->get_unsynced_elements( $taxonomy, $sec_lang );
		$this->assertCount( 2, $unsynced );

		$found_a_orig_sync = false;
		$found_b_orig_sync = false;

		foreach ( $unsynced as $changes ) {
			if ( $changes->translated_id == $terms[ 'b' ][ 'term_taxonomy_id' ] && !$changes->correct_parent ) {
				$found_a_orig_sync = true;
			}
			if ( $changes->translated_id == $terms[ 'a' ][ 'term_taxonomy_id' ] && $terms[ 'b' ][ 'term_id' ] == $changes->correct_parent ) {
				$found_b_orig_sync = true;
			}
		}

		$this->assertTrue( $found_a_orig_sync );
		$this->assertTrue( $found_b_orig_sync );
	}
}