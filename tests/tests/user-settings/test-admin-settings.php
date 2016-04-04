<?php

class Test_Admin_Settings extends WPML_UnitTestCase {

	function test_set_admin_language() {
		global $sitepress;
		$active_lang = 'de';

		$uid = $this->get_active_uid_with_lang( $active_lang );
		$sitepress->set_admin_language_cookie( $active_lang );
		$sitepress->set_admin_language();
		$this->assertEquals( $active_lang,
			$sitepress->get_user_admin_language( $uid ) );

		$inactive_lang = 'fi';
		update_user_meta( $uid, 'icl_admin_language', $inactive_lang );
		$this->assertEquals( $inactive_lang,
			$sitepress->get_user_admin_language( $uid, true ) );
		$sitepress->set_admin_language_cookie( $inactive_lang );
		$sitepress->set_admin_language();

		$this->assertEquals( $inactive_lang,
			$sitepress->get_user_admin_language( $uid, true ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1619
	 */
	function test_locale() {
		global $sitepress, $wpml_test_is_ajax;

		$active_lang = 'de';
		$this->get_active_uid_with_lang( $active_lang );

		$def_lang = $sitepress->get_default_language();
		set_current_screen( 'front' );

		$sitepress->switch_lang( $def_lang );
		$this->assertEquals( $sitepress->get_locale( $def_lang ),
			$sitepress->locale() );
		set_current_screen( 'dashboard' );
		$this->assertEquals( $sitepress->get_locale( $active_lang ),
			$sitepress->locale() );
		set_current_screen( 'ajax' );
		$wpml_test_is_ajax       = true;
		$_SERVER['HTTP_REFERER'] = admin_url( 'plugins.php' );
		$this->assertEquals( $sitepress->get_locale( $active_lang ),
			$sitepress->locale() );
		$_SERVER['HTTP_REFERER'] = 'http://example.com/index.php';
		$this->assertEquals( $sitepress->get_locale( $def_lang ),
			$sitepress->locale() );
	}

	private function get_active_uid_with_lang( $lang ) {

		$user_factory = new WP_UnitTest_Factory_For_User();
		$user         = $user_factory->create_and_get();
		wp_set_current_user( $user->ID );
		$uid = get_current_user_id();
		$this->assertGreaterThan( 0, $uid, 'No user logged in!' );
		update_user_meta( $uid, 'icl_admin_language', $lang );
		wp_cache_init();

		return $uid;
	}
}