<?php

class Test_WPML_Language_Domain_Validation extends WPML_UnitTestCase {

	private $url = 'http://test.dev';

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_construct() {
		$wp_api = $this->get_wp_api_mock();
		/** @var WP_Http $http */
		$http = $this->getMockBuilder( 'WP_Http' )->disableOriginalConstructor()->getMock();
		new WPML_Language_Domain_Validation( $wp_api, $http, 'ffiii', '' );
	}

	public function test_is_valid() {
		$wp_api = $this->get_wp_api_mock();
		$wp_api->method( 'get_site_url' )->willReturn( $this->url );
		$error      = new WP_Error();
		$http_error = $this->get_http_mock( $error );
		$this->assertFalse( ( new WPML_Language_Domain_Validation( $wp_api,
			$http_error,
			$this->url, '' ) )->is_valid() );
		$http_no_err_site_url = $this->get_http_mock( array(
			'response' => array( 'code' => '200' ),
			'body'     => '<!--' . untrailingslashit( $wp_api->get_site_url() ) . '-->'
		) );
		/** @var WP_Http $http_no_err_site_url */
		$this->assertTrue( ( new WPML_Language_Domain_Validation( $wp_api,
			$http_no_err_site_url,
			$this->url, '' ) )->is_valid() );
	}

	/**
	 * @param WP_Error|array $result
	 *
	 * @return WP_Http|PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_http_mock( $result ) {
		$validation_url       = $this->url . '/?____icl_validate_domain=1';
		$http_no_err_site_url = $this->getMockBuilder( 'WP_Http' )->disableOriginalConstructor()->getMock();
		$http_no_err_site_url->expects( $this->once() )->method( 'request' )->with( $validation_url,
			'timeout=15' )->willReturn( $result );

		return $http_no_err_site_url;
	}
}