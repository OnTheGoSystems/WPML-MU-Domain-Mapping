<?php

class Test_WPML_Translate_Independently extends WPML_UnitTestCase {

	function test_wpml_add_duplicate_identifier() {
		$tmi = new WPML_Translate_Independently();
		$post = new stdClass();
		$post->ID = rand();

		add_post_meta( $post->ID, '_icl_lang_duplicate_of', 1 );

		$expected = '<input type="hidden" id="icl-duplicate-post-nonce" name="icl-duplicate-post-nonce" value="' . wp_create_nonce( 'icl_check_duplicates' ) . '" />';
		$expected .= '<input type="hidden" id="icl-duplicate-post" name="icl-duplicate-post" value="' . $post->ID .'"/>';
		ob_start();
		$tmi->wpml_add_duplicate_identifier( $post );
		$out = ob_get_clean();

		$this->assertEquals( $expected, $out );

		delete_post_meta( $post->ID, '_icl_lang_duplicate_of' );

		ob_start();
		$tmi->wpml_add_duplicate_identifier( $post );
		$out = ob_get_clean();

		$this->assertEquals( '', $out );
	}
}