<?php

require_once ICL_PLUGIN_PATH . '/inc/wpml-post-edit-ajax.class.php';

class Test_WPML_Post_Edit_Ajax extends WPML_UnitTestCase {

	/**
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1574
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1463
	 */
	function test_copy_from_original_fields() {
		/** @var WPML_Post_Translation $wpml_post_translations */
		global $sitepress, $wpml_post_translations;
		$def_lang = $sitepress->get_default_language();

		$post_id = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );

		$post_arr                   = get_post( $post_id, ARRAY_A );
		$post_arr[ 'post_content' ] = "A\nB\nC";
		wp_update_post( $post_arr );

		$tests = array ( );
		$tests[] = array( 'type'     => 'rich',
						  'expected' => '<p>A<br/>B<br/>C</p>' );
		$tests[] = array( 'type'     => 'html',
						  'expected' => "A\nB\nC" );
		
		foreach ( $tests as $test ) {
			$copied_content = WPML_Post_Edit_Ajax::copy_from_original_fields(
				$test[ 'type' ],
				$test[ 'type' ],
				$wpml_post_translations->get_element_trid( $post_id ),
				$wpml_post_translations->get_element_lang_code( $post_id )
			);
	
			$expected_content = $test[ 'expected' ];
	
			$this->assertNotEmpty( $copied_content );
			$this->assertArrayHasKey( 'content', $copied_content );
			$this->assertDiscardWhitespace(
				$expected_content,
				$copied_content[ 'content' ]
			);
		}
	}
}