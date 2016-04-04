<?php
require_once ICL_PLUGIN_PATH . '/inc/post-translation/wpml-root-page-actions.class.php';

class Test_WPML_Root_Page_Actions extends WPML_UnitTestCase {

	function test_get_root_page_id() {
		list( $subject, $root_id ) = $this->get_test_subject();
		/** @var WPML_Root_Page_Actions $subject */
		$this->assertEquals( $root_id, $subject->get_root_page_id() );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1957
	 */
	function test_exclude_root_page_menu_item() {
		list( $subject, $root_id ) = $this->get_test_subject();
		/** @var WPML_Root_Page_Actions $subject */
		$items = array( 'test', (object) array( 'object_id' => $root_id, 'type' => 'post_type' ) );

		$this->assertEquals( array( 'test' ), $subject->exclude_root_page_menu_item( $items ) );
	}

	/**
	 * @return array
	 */
	private function get_test_subject() {
		$root_id          = wpml_test_insert_post( 'en', 'page' );
		$sp_settings_mock = array(
			'language_negotiation_type' => 1,
			'urls' => array(
				'root_page'                      => $root_id,
				'directory_for_default_language' => 1,
				'show_on_root'                   => 'page'
			)
		);

		$subject          = new WPML_Root_Page_Actions( $sp_settings_mock );

		return array( $subject, $root_id );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2664
	 */
	public function test_wpml_home_url_parse_query() {
		// No Root Page.
		$empty_query = $this->get_wp_query_mock();
		$empty_query->method( 'is_main_query' )->willReturn( true );
		$sp_settings_mock = array(
			'language_negotiation_type' => 2,
		);

		$subject          = new WPML_Root_Page_Actions( $sp_settings_mock );
		$parsed_query = $subject->wpml_home_url_parse_query( $empty_query );
		$this->assertEquals( false, isset( $parsed_query->query['page_id'] ) );

		// With Root page.
		$empty_query = $this->get_wp_query_mock();
		$empty_query->method( 'is_main_query' )->willReturn( true );
		list( $subject, $root_id ) = $this->get_test_subject();
		$parsed_query = $subject->wpml_home_url_parse_query( $empty_query );
		$this->assertEquals( $root_id, $parsed_query->query_vars['page_id'] );
		$this->assertEquals( $root_id, $parsed_query->query['page_id'] );
		$this->assertEquals( 1, $parsed_query->is_page );
		$this->assertEquals( new WP_Post( get_post( $root_id ) ), $parsed_query->queried_object );
		$this->assertEquals( '', $parsed_query->query_vars['error'] );
		$this->assertEquals( false, $parsed_query->is_404 );
		$this->assertEquals( null, $parsed_query->query['error'] );

		// Not the main query.
		$empty_query = $this->get_wp_query_mock();
		$empty_query->method( 'is_main_query' )->willReturn( false );
		$parsed_query = $subject->wpml_home_url_parse_query( $empty_query );
		$this->assertEquals( false, isset( $parsed_query->query['page_id'] ) );
	}
}