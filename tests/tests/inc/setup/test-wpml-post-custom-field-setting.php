<?php

class Test_WPML_Post_Custom_Field_Setting extends WPML_UnitTestCase {

	public function test_status() {
		global $iclTranslationManagement;

		$settings_factory = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );
		$this->assertEquals( WPML_IGNORE_CUSTOM_FIELD, $settings_factory->post_meta_setting( rand_str() )->status() );
	}

	public function test_excluded() {
		global $iclTranslationManagement;

		$settings_factory = new WPML_Custom_Field_Setting_Factory( $iclTranslationManagement );
		foreach (
			array(
				'_edit_last',
				'_edit_lock',
				'_wp_page_template',
				'_wp_attachment_metadata',
				'_icl_translator_note',
				'_alp_processed',
				'_pingme',
				'_encloseme',
				'_icl_lang_duplicate_of',
				'_wpml_media_duplicate',
				'wpml_media_processed',
				'_wpml_media_featured',
				'_thumbnail_id'
			) as $key
		) {
			$this->assertTrue( $settings_factory->post_meta_setting( $key )->excluded() );
		}
	}
}