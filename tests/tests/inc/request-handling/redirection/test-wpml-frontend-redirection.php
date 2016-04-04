<?php

class Test_WPML_Frontend_Redirection extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2452
	 */
	public function test_maybe_redirect() {
		global $wpml_language_resolution;

		$wpml_request_handler = $this->getMockBuilder( 'WPML_Frontend_Request' )
		                             ->disableOriginalConstructor()->getMock();
		$lang                 = 'de';
		$wpml_request_handler->method( 'get_requested_lang' )->willReturn( $lang );
		$redirect_helper = $this->getMockForAbstractClass( 'WPML_Redirection',
			array(), '', false );
		$url             = rand_str();
		$redirect_helper->method( 'get_redirect_target' )
		                ->withAnyParameters()
		                ->willReturnOnConsecutiveCalls( false, $url );
		$wp_api = $this->get_wp_api_mock();
		$wp_api->expects( $this->once() )->method( 'wp_safe_redirect' )->with( $url,
			302 );
		$sitepress_mock = $this->get_sitepress_mock( $wp_api );
		/**
		 * @var WPML_Frontend_Request $wpml_request_handler
		 * @var WPML_Redirection      $redirect_helper
		 */
		$subject = new WPML_Frontend_Redirection( $sitepress_mock,
			$wpml_request_handler, $redirect_helper,
			$wpml_language_resolution );
		// First call, not redirect target, will return request lang.
		$this->assertEquals( $lang, $subject->maybe_redirect() );
		// Second call going for the actual redirection.
		$subject->maybe_redirect();
	}
}