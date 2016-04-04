<?php
require_once 'test-request.php';

class Test_WPML_Frontend_Request extends Test_WPML_Request_Handler {

	public function test_cookie_language() {
		$url_converter = $this->get_url_converter_mock();
		$pagenow       = 'index.php';
		foreach ( array( 'en', 'fr' ) as $lang ) {
			$cookie = $this->get_mock_cookie( $lang );
			$cookie->method( 'headers_sent' )->willReturn( false );
			$cookie->expects( $this->once() )->method( 'set_cookie' )
			       ->with(
				       $this->anything(),
				       $lang,
				       $this->anything(),
				       $this->anything(),
				       $this->anything()
			       );
			$subject = new WPML_Frontend_Request( $url_converter, array(
				'en',
				'fr',
				'es'
			), 'en', $cookie, $pagenow );
			$subject->set_language_cookie( $lang );
			$this->assertEquals( $lang, $subject->get_cookie_lang() );
		}
	}

	/**
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2452
	 */
	public function test_get_requested_lang() {
		$url_converter = $this->get_url_converter_mock();
		$pagenow       = 'wp-comments-post.php';
		$lang          = 'de';
		$cookie        = $this->get_mock_cookie( $lang );
		$subject       = new WPML_Frontend_Request(
			$url_converter, array(
			'en',
			'de'
		), 'en', $cookie, $pagenow );
		$this->assertEquals( $lang, $subject->get_requested_lang() );
	}
}