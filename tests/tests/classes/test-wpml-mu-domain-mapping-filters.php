<?php

require_once dirname( __FILE__ ) . '/../../../classes/class-wpml-mu-domain-mapping-filters.php';

class Test_WPML_MU_Domain_Mapping_Filters extends WPML_UnitTestCase {

	public function test_init_hooks() {
		global $sitepress, $wpdb;

		$subject = new WPML_MU_Domain_Mapping_Filters( $wpdb, $sitepress );
		$subject->init_hooks();

		$priority = has_filter( 'wpml_url_converter_get_abs_home', array( $subject, 'url_converter_get_abs_home_filter' ) );
		$this->assertEquals( 10, $priority );
	}

	public function test_url_converter_get_abs_home_filter() {
		$bk_home_url   = get_option( 'home' );
		$home_url      = 'http://example.com/';
		$home_url_ssl  = 'https://example.com/';
		$mapped_domain = 'mydomain.org';

		$wpdb = $this->get_wpdb_mock();
		$wpdb->method( 'get_var' )->willReturn( $mapped_domain );
		$wpml_wp_api = $this->get_wp_api_mock();
		$wpml_wp_api->method( 'constant' )->with( 'DOMAIN_MAPPING' )->willReturn( 1 );
		$wpml_wp_api->method( 'is_main_site' )->willReturn( false );
		$sitepress = $this->get_sitepress_mock( $wpml_wp_api );

		// Test with http
		update_option( 'home', $home_url );
		$subject = new WPML_MU_Domain_Mapping_Filters( $wpdb, $sitepress );
		$filtered_abs_url = $subject->url_converter_get_abs_home_filter( $home_url );
		$expected_url     = trailingslashit( 'http://' . $mapped_domain );
		$this->assertEquals( $expected_url, $filtered_abs_url );

		// Test with https
		update_option( 'home', $home_url_ssl );
		$subject = new WPML_MU_Domain_Mapping_Filters( $wpdb, $sitepress );
		$filtered_abs_url = $subject->url_converter_get_abs_home_filter( $home_url_ssl );
		$expected_url     = trailingslashit( 'https://' . $mapped_domain );
		$this->assertEquals( $expected_url, $filtered_abs_url );

		// Restore
		update_option( 'home', $bk_home_url );
	}
}