<?php

class Test_WPML_Update_Term_Count extends WPML_UnitTestCase {

	public function test_update_for_post() {
		$wp_api   = $this->get_wp_api_mock();
		$post_id  = rand();
		$taxonomy = rand_str();
		$ttid     = rand();
		$wp_api->method( 'get_taxonomies' )->willReturn( array( $taxonomy ) );
		$wp_api->expects( $this->once() )->method( 'wp_get_post_terms' )->with( $post_id,
			$taxonomy,
			array() )->willReturn( array( (object) ( array( 'term_taxonomy_id' => $ttid ) ) ) );
		$wp_api->expects( $this->once() )->method( 'wp_update_term_count' )->with( $ttid,
			$taxonomy, false );
		$subject = new WPML_Update_Term_Count( $wp_api );
		$subject->update_for_post( $post_id );
	}
}