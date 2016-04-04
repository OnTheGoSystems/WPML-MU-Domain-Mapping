<?php

class Test_Sticky_Post_Sync extends WPML_UnitTestCase {

	public function setUp() {
		parent::setUp();
		delete_option( 'sticky_posts' );
		icl_set_setting( 'sync_sticky_flag', 1, true );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-984
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1446
	 */
	function test_sync_sticky() {
		/**
		 * @var SitePress             $sitepress
		 * @var WPML_Post_Translation $wpml_post_translations
		 */
		global $wpml_post_translations, $sitepress;

		$wpml_post_translations->init();
		$def_lang    = $sitepress->get_default_language();
		$def_title   = 'Sticky Original';
		$sec_title   = 'Sticky Translation';
		$third_title = 'Sticky Second Translation';
		$sec_lang    = 'de';

		$sitepress->switch_lang( $def_lang );
		add_filter( 'pre_option_sticky_posts', array( $sitepress, 'option_sticky_posts' ), 10, 2 );
		add_filter( 'pre_update_option_sticky_posts',
		            array( $wpml_post_translations, 'pre_update_option_sticky_posts' ),
		            10,
		            1 );
		$orig_sticky = wpml_test_insert_post( $def_lang, 'post', false, $def_title );
		stick_post( $orig_sticky );
		$this->assertEquals( array( $orig_sticky ), get_option( 'sticky_posts' ) );
		delete_option( 'sticky_posts' );
		$sitepress->switch_lang( $sec_lang );
		stick_post( $orig_sticky );

		$sitepress->switch_lang( $def_lang );
		$this->assertEquals( $def_lang, $wpml_post_translations->get_element_lang_code( $orig_sticky ) );
		$this->assertEquals( array( $orig_sticky ), get_option( 'sticky_posts' ) );

		$trid = $wpml_post_translations->get_element_trid( $orig_sticky );

		$sitepress->switch_lang( $sec_lang );

		$sec_lang_sticky = wpml_test_insert_post( $sec_lang, 'post', $trid, $sec_title );
		$this->assertEquals( $sec_lang, $wpml_post_translations->get_element_lang_code( $sec_lang_sticky ) );

		$sitepress->switch_lang( $def_lang );
		$this->assertEquals( array( $orig_sticky ), get_option( 'sticky_posts' ) );
		$sitepress->switch_lang( $sec_lang );
		$this->assertTrue( (bool) has_filter( 'pre_option_sticky_posts', array( $sitepress, 'option_sticky_posts' ) ) );
		$this->assertEquals( array( $sec_lang_sticky ), get_option( 'sticky_posts' ) );

		$this->check_toggle_sticky_flag( $def_lang, $orig_sticky, $trid, $third_title );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1950
	 *
	 * @param string $def_lang
	 * @param int    $orig_sticky
	 * @param int    $trid
	 * @param string $third_title
	 */
	private function check_toggle_sticky_flag( $def_lang, $orig_sticky, $trid, $third_title ) {
		global $sitepress;

		$sitepress->set_setting( 'sync_sticky_flag', 0, true );

		$third_lang = 'fr';
		$sitepress->switch_lang( $third_lang );
		$third_post = wpml_test_insert_post( $third_lang, 'post', $trid, $third_title );

		$this->assertEmpty( get_option( 'sticky_posts' ) );

		$sitepress->switch_lang( $def_lang );
		$this->assertEquals( array( $orig_sticky ), array_values( get_option( 'sticky_posts' ) ) );

		$sitepress->switch_lang( $third_lang );
		$sitepress->set_setting( 'sync_sticky_flag', 1, true );
		wp_update_post( $third_post, get_post( $third_post, ARRAY_A ) );
		$this->assertEquals( array( $third_post ), array_values( get_option( 'sticky_posts' ) ) );
		$sitepress->set_setting( 'sync_sticky_flag', 0, true );
		$sitepress->switch_lang( $def_lang );
		$new_post = wpml_test_insert_post( $def_lang, 'post' );
		stick_post( $new_post );
		$sitepress->switch_lang( $third_lang );
		$this->assertEquals( array( $third_post ), array_values( get_option( 'sticky_posts' ) ) );
	}

	function tearDown() {
		delete_option( 'sticky_posts' );
		parent::tearDown();
	}
}