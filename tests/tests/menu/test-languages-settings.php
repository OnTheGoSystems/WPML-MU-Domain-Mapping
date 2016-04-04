<?php

/**
 * @group languages
 */
class Test_Languages_Settings extends WPML_UnitTestCase {

	function setUp() {
		global $iclTranslationManagement, $sitepress;
		parent::setUp();

		$sitepress->set_setting( 'sync_comments_on_duplicates', 1, true );
		wpml_load_core_tm();
		$iclTranslationManagement->init();
	}

	function test_languages_order_after_adding_a_new_language() {
		/* @var $wpml_language_resolution WPML_Language_Resolution */
		global $wpml_language_resolution, $sitepress;

		$active_langs     = $wpml_language_resolution->get_active_language_codes();
		$setup_helper     = wpml_get_setup_instance();
		$setup_helper->set_active_languages( $active_langs );
		$languages_order  = $sitepress->get_setting( 'languages_order' );
		$new_codes        = array( 'zh-hant', 'zh-hans' );
		$new_active_langs = array_merge( $active_langs, $new_codes );
		$setup_helper->set_active_languages( $new_active_langs );
		$new_languages_order = $sitepress->get_setting( 'languages_order' );
		$expected_order_count = count( $new_active_langs );
		$actual_order_count = count( $new_languages_order );
		$this->assertEquals( $expected_order_count, $actual_order_count );
		$this->assertEquals( 'zh-hans', $new_languages_order[ count( $active_langs ) ] );
		$this->assertEquals( 'zh-hant', $new_languages_order[ count( $active_langs ) + 1 ] );

		shuffle($languages_order);

		$active_langs     = $wpml_language_resolution->get_active_language_codes();
		$new_active_langs = array_merge( $active_langs, array('pl') );
		$setup_helper->set_active_languages( $new_active_langs );
		$new_languages_order = $sitepress->get_setting( 'languages_order' );
		$expected_order_count = count( $new_active_langs );
		$actual_order_count = count( $new_languages_order );
		$this->assertEquals( $expected_order_count, $actual_order_count );
		$this->assertTrue( in_array( 'pl', $new_languages_order, true ) );

		asort($languages_order);

		$active_langs     = $wpml_language_resolution->get_active_language_codes();
		$new_active_langs = array_merge( $active_langs, array('tr', 'uk') );
		$setup_helper->set_active_languages( $new_active_langs );
		$new_languages_order = $sitepress->get_setting( 'languages_order' );
		$expected_order_count = count( $new_active_langs );
		$actual_order_count = count( $new_languages_order );
		$this->assertEquals( $expected_order_count, $actual_order_count );
		$this->assertTrue( in_array( 'pl', $new_languages_order, true ) );

		arsort($languages_order);

		$new_active_langs = array_merge( $active_langs, array('ta', 'is') );
		$setup_helper->set_active_languages( $new_active_langs );
		$new_languages_order = $sitepress->get_setting( 'languages_order' );
		$expected_order_count = count( $new_active_langs );
		$actual_order_count = count( $new_languages_order );
		$this->assertEquals( $expected_order_count, $actual_order_count );
		$this->assertTrue( in_array( 'pl', $new_languages_order, true ) );
	}

}