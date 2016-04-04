<?php

class Test_WPML_Browser_Redirect extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2612
	 */
	public function test_no_browser_redirection_on_root() {
		$sitepress = $this->get_sitepress_mock();
		$root_page = $this->getMockBuilder( 'WPML_Root_Page_Actions' )->disableOriginalConstructor()->getMock();
		$root_page->method( 'is_url_root_page' )->willReturn( true );
		$root_page->method( 'get_root_page_id' )->willReturn( 17 );
		$sitepress->method( 'get_root_page_utils' )->willReturn( $root_page);

		set_current_screen( 'front' );

		$subject = new WPML_Browser_Redirect( $sitepress );
		$subject->init();
		$this->assertFalse( has_filter( 'wp_print_scripts', array( $subject, 'scripts' ) ) );
		unset( $subject, $sitepress, $root_page );

		$sitepress = $this->get_sitepress_mock();
		$root_page = $this->getMockBuilder( 'WPML_Root_Page_Actions' )->disableOriginalConstructor()->getMock();
		$root_page->method( 'is_url_root_page' )->willReturn( false );
		$root_page->method( 'get_root_page_id' )->willReturn( 0 );
		$sitepress->method( 'get_root_page_utils' )->willReturn( $root_page);

		$subject = new WPML_Browser_Redirect( $sitepress );
		$subject->init();
		$this->assertEquals( 10, has_filter( 'wp_print_scripts', array( $subject, 'scripts' ) ) );
	}
}