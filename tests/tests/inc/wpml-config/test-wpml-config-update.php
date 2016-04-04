<?php

class Test_WPML_Config_Update extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1954
	 *
	 */
	function test_run() {

		$json_response = json_encode( array(
			"plugins" => array( array( "name" => "WPMLT", "hash" => "123", "path" => "http://cdn" ) ),
			"themes"  => array( array( "name" => "Twenty", "hash" => "123", "path" => "http://cdn" ) )
		) );

		$this->check_with_constant();
		$this->check_without_constant( $json_response );
		$this->check_without_wpml_config_arr( $json_response );
	}

	private function check_with_constant() {
		$wp_api = $this->get_wp_api_mock();
		$wp_api->method( 'constant' )->with( 'ICL_REMOTE_WPML_CONFIG_DISABLED' )->willReturn( true );
		$sitepress = $this->get_sitepress_mock( $wp_api );
		$http      = $this->get_http_mock();
		$subject   = new WPML_Config_Update( $sitepress, $http );

		update_option( "wpml_config_index", "Test Config Arr" );
		update_option( "wpml_config_index_updated", "Test Config Index Updated" );
		update_option( "wpml_config_files_arr", "Test Config Arr" );

		$subject->run();

		$this->assertFalse( get_option( "wpml_config_index" ) );
		$this->assertFalse( get_option( "wpml_config_index_updated" ) );
		$this->assertFalse( get_option( "wpml_config_files_arr" ) );

	}

	private function check_without_constant( $json_response ) {
		$wp_api    = $this->get_wp_api_mock();
		$http      = $this->get_http_mock();
		$sitepress = $this->get_sitepress_mock( $wp_api );

		$wp_api->method( 'constant' )->with( 'ICL_REMOTE_WPML_CONFIG_DISABLED' )->willReturn( false );
		$wp_api->method( 'get_theme_name' )->willReturn( "Twenty" );
		$wp_api->method( 'get_plugins' )->willReturn( array( array( "Name" => "WPMLT" ) ) );
		$http->method( "get" )->willReturn( array( "response" => array( "code" => 200 ), "body" => $json_response ) );

		$subject = new WPML_Config_Update( $sitepress, $http );

		update_option( "wpml_config_index", "Test Config Arr" );
		update_option( "wpml_config_index_updated", "Test Config Index Updated" );
		update_option( "wpml_config_files_arr", (object) array(
			"themes"  => array( "name" => "WPMLT" ),
			"plugins" => array( "name" => "Twenty" )
		) );

		$subject->run();

		$this->assertNotFalse( get_option( "wpml_config_index" ) );
		$this->assertNotFalse( get_option( "wpml_config_index_updated" ) );
		$this->assertNotFalse( get_option( "wpml_config_files_arr" ) );

	}

	private function check_without_wpml_config_arr( $json_response ) {
		$wp_api    = $this->get_wp_api_mock();
		$http      = $this->get_http_mock();
		$sitepress = $this->get_sitepress_mock( $wp_api );

		$wp_api->method( 'constant' )->with( 'ICL_REMOTE_WPML_CONFIG_DISABLED' )->willReturn( false );
		$wp_api->method( 'get_theme_name' )->willReturn( "Twentyy" );
		$wp_api->method( 'get_plugins' )->willReturn( array( array( "Name" => "WPMLT" ) ) );
		$http->method( "get" )->willReturn( array( "response" => array( "code" => 200 ), "body" => $json_response ) );

		$subject = new WPML_Config_Update( $sitepress, $http );

		delete_option( "wpml_config_files_arr" );

		$subject->run();

		$this->assertNotFalse( get_option( "wpml_config_files_arr" ) );

	}

	private function get_http_mock() {

		return $this->getMockBuilder( "WP_Http" )->disableOriginalConstructor()->getMock();
	}
}