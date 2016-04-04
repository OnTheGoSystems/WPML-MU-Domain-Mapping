<?php

class Test_WPML_Allowed_Redirect_Hosts extends WPML_UnitTestCase {

	public function setUp() {
		parent::setUp();
		
		$this->domains = array( 'fr' => 'fr.test.com',
								'de' => 'de.test.com'
								);
		$this->default_home     = 'en.test.com';
		$this->default_language = 'en';
		$this->current_language = 'de';
		
		$this->expected = array( 'fr.test.com', 'de.test.com', 'en.test.com' );
	}

	public function test_get_hosts() {
		$wp_api    = $this->get_wp_api_mock();
		$sitepress = $this->get_sitepress_mock( $wp_api );
		$sitepress->method( 'get_setting' )->willReturn( $this->domains );
		$sitepress->method( 'get_default_language' )->willReturn( $this->default_language );
		$sitepress->method( 'convert_url' )->willReturn( 'http://' . $this->default_home );
		$sitepress->method( 'get_active_languages' )->willReturn( array_merge( $this->domains, array( $this->default_language => 1 ) ) );
		
		$subject = new WPML_Allowed_Redirect_Hosts( $sitepress );
		$hosts = $subject->get_hosts( array() );
		
		$this->assertEquals( 3, count( $hosts ) );
		
		$this->assertEquals( $this->expected , $hosts );
	}
}