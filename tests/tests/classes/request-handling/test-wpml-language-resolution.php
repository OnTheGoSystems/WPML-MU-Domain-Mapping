<?php

class Test_WPML_Language_Resolution extends WPML_UnitTestCase {

	/**
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2459
	 */
	public function test_current_lang_filter() {
		$this->check_preview_id();
		$this->check_use_referer_language();
		$this->check_all_is_legal_in_admin();
	}

	/**
	 * Checks that "all" is not filtered out as a language in the backend,
	 * while rerouted to the default language in the frontend
	 */
	private function check_all_is_legal_in_admin() {
		$default_language = 'en';
		set_current_screen( 'front' );
		$subject = new WPML_Language_Resolution( array(
			$default_language,
			'de'
		),
			$default_language );
		$this->assertEquals( $default_language,
			$subject->current_lang_filter( 'all' ) );
		set_current_screen( 'dashboard' );
		$this->assertEquals( 'all', $subject->current_lang_filter( 'all' ) );
	}

	/**
	 * Checks if the http referer is correctly used to identify a request's
	 * language, when the given requested action requires it.
	 */
	private function check_use_referer_language() {
		$subject                 = new WPML_Language_Resolution( array(
			'en',
			'de'
		),
			'en' );
		$_POST['action']         = 'wp-link-ajax';
		$_SERVER['HTTP_REFERER'] = 'http://example.com/wp-admin/edit.php?lang=de';
		$this->assertEquals( 'de', $subject->current_lang_filter( 'en' ) );
		unset( $_POST['action'] );
		unset( $_SERVER['HTTP_REFERER'] );
	}

	/**
	 * Checks if requests containing a preview ID are correctly routed
	 * to the language of the previewed element.
	 */
	private function check_preview_id() {
		$default_language   = 'en';
		$secondary_language = 'fr';
		$subject            = new WPML_Language_Resolution(
			array(
				'de',
				$secondary_language,
				$default_language
			), $default_language );
		$this->assertEquals(
			$default_language,
			$subject->current_lang_filter( 'foo' ) );
		$secondary_lang_post_id = wpml_test_insert_post( $secondary_language );
		$_GET['preview_id']     = $secondary_lang_post_id;
		$this->assertEquals(
			$secondary_language,
			$subject->current_lang_filter( 'foo' ) );
		unset( $_GET['preview_id'] );
	}
}