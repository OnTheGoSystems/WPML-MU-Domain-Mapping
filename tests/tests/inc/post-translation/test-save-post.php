<?php

class Test_Save_Post extends WPML_UnitTestCase {
	/** @var WP_UnitTest_Factory_For_Post $post_factory */
	private $post_factory;

	public function setUP() {
		parent::setUp();
		$this->post_factory = new WP_UnitTest_Factory_For_Post();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1825
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2361
	 */
	function test_save_page_template() {
		global $wpml_post_translations, $sitepress_settings, $wpml_language_resolution, $wpml_request_handler;

		set_current_screen( 'post' );
		$wpml_request_handler   = wpml_load_request_handler( true, $wpml_language_resolution->get_active_language_codes(), $sitepress_settings['default_language'] );
		$wpml_post_translations = $this->admin_post_actions( 0 );
		list( $source_lang, $target_lang ) = $this->get_source_and_target_languages( 1 );
		$original_id   = wpml_test_insert_post( $source_lang, 'page', false );
		$trid          = $wpml_post_translations->get_element_trid( $original_id );
		$translated_id = wpml_test_insert_post( $target_lang, 'page', $trid );
		$template      = 'test.php';
		update_post_meta( $translated_id, '_wp_page_template', $template );
		$wpml_post_translations->save_post_actions( $translated_id, get_post( $translated_id ) );
		$this->assertEquals( $template, get_post_meta( $translated_id, '_wp_page_template', true ) );
		$wpml_post_translations = $this->admin_post_actions( 1 );
		$template_revert        = 'test_revert.php';
		update_post_meta( $original_id, '_wp_page_template', $template_revert );
		$wpml_post_translations->save_post_actions( $original_id, get_post( $original_id ) );
		$this->assertEquals( $template_revert, get_post_meta( $original_id, '_wp_page_template', true ) );
		$this->assertEquals( $template_revert, get_post_meta( $translated_id, '_wp_page_template', true ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1446
	 */
	function test_save_post_frontend() {
		global $wpml_post_translations, $sitepress_settings, $wpdb;

		$wpml_post_translations = new WPML_Frontend_Post_Actions( $sitepress_settings, $wpdb );

		$this->check_save_post_effects( $wpml_post_translations );
	}

	function test_save_post_backend() {
		global $wpml_post_translations, $sitepress_settings, $wpdb;

		$wpml_post_translations = new WPML_Admin_Post_Actions( $sitepress_settings, $wpdb );

		$this->check_save_post_effects( $wpml_post_translations );
	}

	/**
	 * @param WPML_Post_Translation $wpml_post_translations
	 */
	private function check_save_post_effects( $wpml_post_translations ) {
		global $sitepress;

		remove_all_actions( 'save_post' );
		$post_no_action = $this->post_factory->create_and_get();
		$this->assertNull( $wpml_post_translations->get_element_lang_code( $post_no_action->ID ) );
		$wpml_post_translations->init();
		$post_with_action = $this->post_factory->create_and_get();
		$current_lang     = $sitepress->get_current_language();
		$this->assertTrue( (bool) $current_lang );
		$this->assertEquals( $current_lang, $wpml_post_translations->get_element_lang_code( $post_with_action->ID ) );

		remove_action( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100 );
	}

	private function admin_post_actions( $sync_templates ) {
		global $wpml_post_translations, $sitepress_settings, $wpdb;

		icl_set_setting( 'sync_page_template', $sync_templates, true );
		remove_all_actions( 'save_post' );
		$wpml_post_translations = new WPML_Admin_Post_Actions( $sitepress_settings, $wpdb );
		$wpml_post_translations->init();

		return $wpml_post_translations;
	}
}