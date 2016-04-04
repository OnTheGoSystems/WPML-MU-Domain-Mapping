<?php

abstract class Test_WPML_Request_Handler extends WPML_UnitTestCase {

	/**
	 * @param $lang
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|WPML_Cookie
	 */
	protected function get_mock_cookie( $lang ) {
		$cookie = $this->getMockBuilder( 'WPML_Cookie' )->disableOriginalConstructor()->getMock();
		$cookie->method( 'get_cookie' )->willReturn( $lang );

		return $cookie;
	}
}