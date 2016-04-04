<?php
require_once 'test-request.php';
require_once ICL_PLUGIN_PATH . '/inc/request-handling/wpml-backend-request.class.php';

class Test_WPML_Backend_Request extends Test_WPML_Request_Handler {

	private $cookie_name_admin     = '_icl_current_admin_language';
	private $cookie_name_front_end = '_icl_current_language';

	public function setUp() {
		parent::setUp();
		global $wpml_language_resolution;
		icl_set_setting( 'hidden_languages', array( 'ru' ), true );
		$wpml_language_resolution = new WPML_Language_Resolution( $wpml_language_resolution->get_active_language_codes(),
																  icl_get_setting( 'default_language' ) );
		set_current_screen( 'plugins.php' );
		
		$backend_request           = $this->get_backend_request();
		$this->cookie_name_admin = '_icl_current_admin_language_' . md5( $backend_request->get_cookie_domain() );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlmedia-75
	 */
	public function test_set_language_cookie() {
		$url_converter_mock = $this->get_url_converter_mock();
		$langs              = array( 'de', 'ru' );
		foreach ( $langs as $lang ) {
			$mock_cookie = $this->get_mock_cookie( $lang );
			$subject     = new WPML_Backend_Request( $url_converter_mock, $langs, 'de', $mock_cookie );
			$subject->set_language_cookie( $lang );
			$this->assertEquals( $lang, $_COOKIE[ $this->cookie_name_admin ] );
		}
	}

	public function test_check_if_admin_action_from_referer() {
		$backend_request           = $this->get_backend_request();
		$_SERVER[ 'HTTP_REFERER' ] = 'test.de/wp-admin/index.php';
		$this->assertTrue( $backend_request->check_if_admin_action_from_referer() );
		$_SERVER[ 'HTTP_REFERER' ] = 'test.de/test.php';
		$this->assertFalse( $backend_request->check_if_admin_action_from_referer() );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1594
	 */
	function test_get_requested_lang() {
		global $wpml_language_resolution;

		$backend_request = $this->get_backend_request();
		$sec_lang        = 'de';
		$def_lang        = 'en';
		$hidden_lang     = 'ru';
		$inactive_lang   = 'fooo';
		$this->assertTrue( $wpml_language_resolution->is_language_active( $sec_lang ) );
		$this->assertTrue( $wpml_language_resolution->is_language_active( $def_lang ) );
		$this->assertTrue( $wpml_language_resolution->is_language_active( $hidden_lang ) );
		$this->assertFalse( $wpml_language_resolution->is_language_active( $inactive_lang ) );
		$this->check_get_lang( $sec_lang, $backend_request );
		$this->check_enforce_default( $def_lang, $backend_request, $sec_lang );
		$this->check_get_lang( $def_lang, $backend_request );
		$this->check_enforce_default( $def_lang, $backend_request, $def_lang );
		$this->check_get_lang( $inactive_lang, $backend_request, $def_lang );
		$this->check_enforce_default( $def_lang, $backend_request, $inactive_lang );
		$this->check_get_lang( 'all', $backend_request );
		$this->check_enforce_default( $def_lang, $backend_request, 'all' );
		$this->check_icl_post_language( $def_lang, $sec_lang, $inactive_lang, $backend_request );
		$this->check_posts_lang_recognition( $def_lang, $sec_lang, $backend_request );
		$this->check_fallback_to_cookie( $def_lang, $sec_lang, $inactive_lang, $backend_request );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1558
	 *
	 * @param string               $def_lang
	 * @param string               $sec_lang
	 * @param string               $inactive_lang
	 * @param WPML_Backend_Request $backend_request
	 */
	private function check_fallback_to_cookie( $def_lang, $sec_lang, $inactive_lang, $backend_request ) {
		global $wpml_language_resolution;
		$this->tearDown();
		$lang_codes = $wpml_language_resolution->get_active_language_codes();
		foreach ( $lang_codes as $code ) {
			$this->check_ajax_front_back_separation( $code, $def_lang, $def_lang, $backend_request );
			$this->check_ajax_front_back_separation( $code, $sec_lang, $sec_lang, $backend_request );
			$this->check_ajax_front_back_separation( $code, $inactive_lang, $def_lang, $backend_request );
			unset( $_COOKIE[ $this->cookie_name_admin ] );
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1619
	 *
	 * @param string               $code
	 * @param string               $admin_code
	 * @param string               $expected_admin_code
	 * @param WPML_Backend_Request $backend_request
	 */
	private function check_ajax_front_back_separation( $code, $admin_code, $expected_admin_code, $backend_request ) {
		global $wpml_test_is_ajax;
		$_COOKIE[ $this->cookie_name_front_end ] = $code;
		$_COOKIE[ $this->cookie_name_admin ]     = $admin_code;
		$wpml_test_is_ajax                       = false;
		$_SERVER[ 'HTTP_REFERER' ] = 'test.de/wp-admin/test.php';
		$this->assertEquals( $expected_admin_code, $backend_request->get_requested_lang() );
		$_SERVER[ 'HTTP_REFERER' ]               = 'test.de/test.php';
		$this->assertEquals( $expected_admin_code, $backend_request->get_requested_lang() );
		$wpml_test_is_ajax = true;
		$this->assertEquals( $code, $backend_request->get_requested_lang() );
		$_SERVER[ 'HTTP_REFERER' ] = 'test.de/wp-admin/test.php';
		$this->assertEquals( $expected_admin_code, $backend_request->get_requested_lang() );
		unset( $_SERVER[ 'HTTP_REFERER' ] );
	}

	/**
	 * @param string               $def_lang
	 * @param string               $sec_lang
	 * @param WPML_Backend_Request $backend_request
	 */
	private function check_posts_lang_recognition( $def_lang, $sec_lang, $backend_request ) {
		global $wpml_post_translations;

		$post_def = wpml_test_insert_post( $def_lang );
		$this->assertEquals( $def_lang, $wpml_post_translations->get_element_lang_code( $post_def ) );
		$_GET[ 'p' ] = $post_def;
		$this->assertEquals( $def_lang, $backend_request->get_requested_lang() );
		$post_sec = wpml_test_insert_post( $sec_lang );
		$this->assertEquals( $sec_lang, $wpml_post_translations->get_element_lang_code( $post_sec ) );
		$_GET[ 'p' ] = $post_sec;
		$this->assertEquals( $sec_lang, $backend_request->get_requested_lang() );
		unset( $_GET[ 'p' ] );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1529
	 *
	 * @param string               $def_lang
	 * @param string               $sec_lang
	 * @param string               $inactive_lang
	 * @param WPML_Backend_Request $backend_request
	 */
	private function check_icl_post_language( $def_lang, $sec_lang, $inactive_lang, $backend_request ) {
		unset( $_GET[ 'page' ] );
		unset( $_GET[ 'lang' ] );
		$this->assertFalse( wpml_is_ajax() );
		$_POST[ 'icl_post_language' ] = $def_lang;
		$this->assertEquals( $def_lang, $backend_request->get_requested_lang() );
		$_POST[ 'icl_post_language' ] = $sec_lang;
		$this->assertEquals( $sec_lang, $backend_request->get_requested_lang() );
		$_POST[ 'icl_post_language' ] = $inactive_lang;
		$this->assertEquals( $def_lang, $backend_request->get_requested_lang() );
		$_POST[ 'icl_post_language' ] = 'all';
		$this->assertEquals( $def_lang, $backend_request->get_requested_lang() );
		unset( $_POST[ 'icl_post_language' ] );
	}

	private function check_enforce_default( $def_lang, $backend_request, $get_lang ) {
		$this->set_st_and_tm_paths();

		$_GET[ 'page' ] = WPML_ST_FOLDER . '/menu/string-translation.php';
		$this->check_get_lang( $get_lang, $backend_request, $def_lang );
		$_GET[ 'page' ] = WPML_TM_FOLDER . '/menu/translations-queue.php';
		$this->check_get_lang( $get_lang, $backend_request, $def_lang );

		unset( $_GET[ 'page' ] );
	}

	private function set_st_and_tm_paths() {
		if ( ! defined( 'WPML_ST_FOLDER' ) ) {
			define( 'WPML_ST_FOLDER', rand_str() );
		}
		if ( ! defined( 'WPML_TM_FOLDER' ) ) {
			define( 'WPML_TM_FOLDER', rand_str() );
		}
	}

	/**
	 * @param string               $lang
	 * @param WPML_Backend_Request $backend_request
	 * @param bool|string          $expected
	 */
	private function check_get_lang( $lang, $backend_request, $expected = false ) {
		$expected       = $expected ? $expected : $lang;
		$_GET[ 'lang' ] = $lang;
		$this->assertEquals( $expected, $backend_request->get_requested_lang() );
	}

	private function get_backend_request() {
		global $sitepress, $wpml_url_converter;
		$active_langs = array_keys( $sitepress->get_active_languages() );

		return new WPML_Backend_Request( $wpml_url_converter, $active_langs, $sitepress->get_default_language(), new WPML_Cookie() );
	}

	function tearDown() {
		unset( $_SERVER[ 'HTTP_REFERER' ] );
		unset( $_GET[ 'lang' ] );
		unset( $_POST[ 'icl_post_language' ] );
		unset( $_GET[ 'p' ] );
		unset( $_COOKIE[ $this->cookie_name_admin ] );
		unset( $_COOKIE[ $this->cookie_name_front_end ] );
		icl_set_setting( 'hidden_languages', array(), true );
		set_current_screen( 'front' );
	}
}