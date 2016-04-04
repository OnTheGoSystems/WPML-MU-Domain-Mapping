<?php

require_once ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-redirect-by-domain.class.php';

class Test_WPML_Redirect_By_Domain extends WPML_UnitTestCase {

	public function setUp() {
		parent::setUp();
		
		$this->domains = array( 'en' => 'en.test.com',
							    'fr' => 'fr.test.com',
								'de' => 'de.test.com'
								);
		$this->default_language = 'en';
		$this->current_language = 'de';
	}
	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2745
	 */
	public function test_hidden_language() {
		
		$this->wp_api = $this->get_wp_api_mock();
		$this->wp_api->method( 'is_admin' )->willReturn( false );

		$this->check_redirect( false, false );
		$this->check_redirect( true, $this->domains[ $this->default_language ] );
	}
	
	public function test_admin_login() {
		$this->wp_api = $this->get_wp_api_mock();
		$this->wp_api->method( 'is_admin' )->willReturn( true );
		$this->wp_api->method( 'user_can' )->willReturn( true );

		$this->check_redirect( false, false );
		$this->check_redirect( true, $this->domains[ $this->default_language ] );

		$this->wp_api = $this->get_wp_api_mock();
		$this->wp_api->method( 'is_admin' )->willReturn( true );
		$this->wp_api->method( 'user_can' )->willReturn( false );
		
		$this->check_redirect( true, $this->domains[ $this->current_language ] . '/wp-login.php' );
	}

	private function check_redirect( $hidden, $expected ) {
		global $pagenow;
		
		$lang_resolution = $this->getMockBuilder( 'WPML_Language_Resolution' )->disableOriginalConstructor()->getMock();
		$lang_resolution->method( 'is_language_hidden' )->willReturn( $hidden );

		$url_converter   = $this->getMockBuilder( 'WPML_Lang_Domains_Converter')->disableOriginalConstructor()->getMock();
		$url_converter->method( 'get_abs_home' )->willReturn( $this->domains[ $this->default_language ] );
		$cookie          = new WPML_Cookie();
		$request_handler = new WPML_Frontend_Request( $url_converter, array_keys( $this->domains ), $this->default_language, $cookie, $pagenow );

		$redirect_by_domain = new WPML_Redirect_By_Domain( $this->domains, $this->wp_api, $request_handler, $url_converter, $lang_resolution );
		
		$target = $redirect_by_domain->get_redirect_target( $this->current_language );
		$this->assertEquals( $expected , $target );
	}
	
}