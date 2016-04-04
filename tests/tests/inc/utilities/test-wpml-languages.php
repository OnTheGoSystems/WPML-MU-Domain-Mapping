<?php

class Test_WPML_Languages extends WPML_UnitTestCase {

	function setUp() {
		parent::setUp();
		icl_set_setting( 'language_negotiation_type', 1, true );
		icl_set_setting( 'languages_order', array(), true );
	}

	function test_sort_ls_languages() {
		global $sitepress;

		$lang_helper = $this->get_lang_order_subject();
		$langs       = $sitepress->get_ls_languages();
		$asc_id      = $lang_helper->sort_ls_languages( $langs, array( 'order' => 'asc', 'orderby' => 'id' ) );
		$desc_id     = $lang_helper->sort_ls_languages( $langs, array( 'order' => 'desc', 'orderby' => 'id' ) );
		$asc_code    = $lang_helper->sort_ls_languages( $langs, array( 'order' => 'asc', 'orderby' => 'code' ) );
		$desc_code   = $lang_helper->sort_ls_languages( $langs, array( 'order' => 'desc', 'orderby' => 'code' ) );

		foreach ( $asc_id as $lang ) {
			$next    = next( $asc_id );
			$next_id = $next && isset( $next[ 'id' ] ) ? $next[ 'id' ] : 0;
			if ( $next_id ) {
				$this->assertGreaterThan( $next_id, $lang[ 'id' ] );
			}
		}

		foreach ( $desc_id as $lang ) {
			$next    = next( $desc_id );
			$next_id = $next && isset( $next[ 'id' ] ) ? $next[ 'id' ] : 0;
			if ( $next_id ) {
				$this->assertLessThan( $next_id, $lang[ 'id' ] );
			}
		}

		foreach ( $asc_code as $key => $lang ) {
			next( $asc_code );
			$next = key( $asc_code );
			if ( $next && $next != $key ) {
				$this->assertGreaterThan( $next, $key );
			}
		}

		foreach ( $desc_code as $key => $lang ) {
			next( $desc_code );
			$next = key( $desc_code );
			if ( $next ) {
				$this->assertLessThan( $next, $key );
			}
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1806
	 */
	function check_order_by_name() {
		$mock_languages = array();
		foreach ( array( 'a', 'b', 'c' ) as $lang ) {
			$mock_languages[ $lang ] = array( 'translated_name' => $lang . 'name' );
		}
		$lang_helper       = $this->get_lang_order_subject();
		$this->assertEquals( $mock_languages,
		                     $lang_helper->sort_ls_languages( $mock_languages, array( 'orderby' => 'name' ) ) );
		$mock_languages_unordered = $mock_languages;
		shuffle( $mock_languages_unordered );
		$this->assertEquals( array_values( $mock_languages ),
		                     array_values( $lang_helper->sort_ls_languages( $mock_languages_unordered,
		                                                                    array( 'orderby' => 'name' ) ) ) );
		$this->assertEquals( array_values( array_reverse( $mock_languages ) ),
		                     array_values( $lang_helper->sort_ls_languages( $mock_languages_unordered,
		                                                                    array(
				                                                                    'orderby' => 'name',
				                                                                    'order'   => 'desc'
		                                                                    ) ) ) );
	}

	private function get_lang_order_subject() {
		$sitepress = $this->get_sitepress_mock();
		/** @var WPML_Term_Translation $term_translation */
		$term_translation = $this->getMockBuilder( 'WPML_Term_Translation' )->disableOriginalConstructor()->getMock();
		/** @var WPML_Frontend_Post_Actions $post_translations */
		$post_translations = $this->getMockBuilder( 'WPML_Frontend_Post_Actions' )->disableOriginalConstructor()->getMock();

		return new WPML_Languages( $term_translation, $sitepress, $post_translations );
	}
}