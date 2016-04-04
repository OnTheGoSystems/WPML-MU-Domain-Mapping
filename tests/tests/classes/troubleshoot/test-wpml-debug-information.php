<?php

class Test_WPML_Debug_Information extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2566
	 */
	function test_run() {

		$this->test_normal_run();
		$this->test_run_with_old_wp();
		$this->test_run_with_old_php();
	}

	public function test_normal_run() {
		$wp_api = $this->get_wp_api_mock();

		$wp_api->method( 'get_plugins' )->willReturn( array( "sitepress-multilingual-cms/sitepress.php" => array( "Description" => "sitepress-multilingual-cms/sitepress.php" ) ) );
		$wp_api->method( 'get_option' )->with( "active_plugins" )->willReturn( array( "sitepress-multilingual-cms/sitepress.php" ) );
		$wp_api->method( 'get_bloginfo' )->with( 'version' )->willReturn( "4.4" );

		$sitepress = $this->get_sitepress_mock( $wp_api );

		$wpdb = $this->get_wpdb_mock();
		$wpdb->method( 'db_version' )->willReturn( '5.6.7' );

		$subject    = new WPML_Debug_Information( $wpdb, $sitepress );
		$debug_data = $subject->run();
		$this->assertInternalType( "array", $debug_data );

		$json_data = $subject->do_json_encode( $debug_data );
		$this->assertJson( $json_data );

		$this->assertEquals( '5.6.7', json_decode( $json_data )->core->Server->MySQLVersion );
	}

	public function test_run_with_old_wp() {
		$wp_api = $this->get_wp_api_mock();
		$wpdb   = $this->get_wpdb_mock();

		$wp_api->method( 'get_plugins' )->willReturn( array( "sitepress-multilingual-cms/sitepress.php" => array( "Description" => "sitepress-multilingual-cms/sitepress.php" ) ) );
		$wp_api->method( 'get_option' )->with( "active_plugins" )->willReturn( array( "sitepress-multilingual-cms/sitepress.php" ) );
		$wp_api->method( 'get_bloginfo' )->with( 'version' )->willReturn( "3.3" );

		$sitepress = $this->get_sitepress_mock( $wp_api );

		$this->setExpectedDeprecated( "get_theme_data" );

		$subject = new WPML_Debug_Information( $wpdb, $sitepress );
		$subject->run();
	}

	public function test_run_with_old_php() {
		$wp_api = $this->get_wp_api_mock();
		$wpdb   = $this->get_wpdb_mock();

		$wp_api->method( 'get_plugins' )->willReturn( array( "sitepress-multilingual-cms/sitepress.php" => array( "Description" => "sitepress-multilingual-cms/sitepress.php" ) ) );
		$wp_api->method( 'get_option' )->with( "active_plugins" )->willReturn( array( "sitepress-multilingual-cms/sitepress.php" ) );
		$wp_api->method( 'get_bloginfo' )->with( 'version' )->willReturn( "4.4" );
		$wp_api->method( "phpversion" )->willReturn( "5.6" );

		$sitepress = $this->get_sitepress_mock( $wp_api );

		$subject    = new WPML_Debug_Information( $wpdb, $sitepress );
		$debug_data = $subject->run();

		$this->assertInternalType( "array", $debug_data );

		$json_data = $subject->do_json_encode( $debug_data );
		$this->assertJson( $json_data );
	}
}