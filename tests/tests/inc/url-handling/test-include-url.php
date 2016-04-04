<?php

class Test_WPML_Include_Url extends WPML_UnitTestCase {
	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2211
	 */
	function test_wpml_filter_include_url() {
		global $wpdb;

		$main_domain = 'example.com';
		$this->switch_to_langs_in_domains( $main_domain );
		$subject = new WPML_Include_Url( $wpdb, 'de.' . $main_domain );
		$this->assertEquals( 'http://de.example.com/path', $subject->filter_include_url( 'http://example.com/path' ) );
		$this->assertEquals( 'https://de.example.com/path', $subject->filter_include_url( 'https://de.example.com/path' ) );
		$this->assertEquals( 'http://google.com/path', $subject->filter_include_url( 'http://google.com/path' ) );
		$subject = new WPML_Include_Url( $wpdb, $main_domain );
		$this->assertEquals( 'http://example.com/path', $subject->filter_include_url( 'http://de.example.com/path' ) );
		$this->assertEquals( 'https://example.com/path', $subject->filter_include_url( 'https://example.com/path' ) );
		$this->assertEquals( 'http://google.com/path', $subject->filter_include_url( 'http://google.com/path' ) );
	}
}