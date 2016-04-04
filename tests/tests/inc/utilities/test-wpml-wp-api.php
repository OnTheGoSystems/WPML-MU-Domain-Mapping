<?php

class Test_WPML_WP_API extends WPML_UnitTestCase {

	function test_is_term_edit_page() {
		global $pagenow;
		$old_pagenow = $pagenow;
		
		$this->check_not_a_taxonomy_page();
		$this->check_not_a_taxonomy_page_with_edit_action();
		$this->check_taxonomy_page_with_no_action();
		$this->check_taxonomy_page_with_edit_action_for_wp44();
		$this->check_term_page_for_wp45();
		
		$pagenow = $old_pagenow;
	}
	
	private function check_not_a_taxonomy_page() {
		global $pagenow;
		$wp_api  = new WPML_WP_API();
		$pagenow = 'edit.php';
		unset( $_GET[ 'action' ] );
		$this->assertFalse( $wp_api->is_term_edit_page() );
	}

	private function check_not_a_taxonomy_page_with_edit_action() {		
		global $pagenow;
		$wp_api  = new WPML_WP_API();
		$pagenow = 'edit.php';
		$_GET[ 'action' ] = 'edit';
		$this->assertFalse( $wp_api->is_term_edit_page() );
	}
		
	private function check_taxonomy_page_with_no_action() {
		global $pagenow;
		$wp_api  = new WPML_WP_API();
		$pagenow = 'edit-tags.php';		
		unset( $_GET[ 'action' ] );
		$this->assertFalse( $wp_api->is_term_edit_page() );
	}
	
	private function check_taxonomy_page_with_edit_action_for_wp44() {
		global $pagenow;
		$wp_api  = new WPML_WP_API();
		$pagenow = 'edit-tags.php';		
		$_GET[ 'action' ] = 'edit';
		$this->assertTrue( $wp_api->is_term_edit_page() );
	}
	
	private function check_term_page_for_wp45() {
		global $pagenow;
		$wp_api  = new WPML_WP_API();
		$pagenow = 'term.php';		
		unset( $_GET[ 'action' ] );
		$this->assertTrue( $wp_api->is_term_edit_page() );
	}

}