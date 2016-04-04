<?php
require_once ICL_PLUGIN_PATH . '/menu/post-menus/post-edit-screen/wpml-sync-custom-field-note.class.php';

class Test_WPML_Sync_Custom_Field_Note extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1982
	 */
	public function test_print_sync_copy_custom_field_note() {
		$wp_api_mock      = $this->get_wp_api_mock();
		$custom_name      = rand_str( 10 );
		$custom_value     = rand_str( 10 );
		$source_lang      = 'de';
		$mock_original_id = rand( 10, 100 );
		$translations     = array( $source_lang => $mock_original_id, 'foo' => 101 );
		$wp_api_mock->method( 'get_post_custom' )->willReturnMap( array(
			                                                          array(
				                                                          $mock_original_id,
				                                                          array( $custom_name => $custom_value )
			                                                          )
		                                                          ) );
		$sitepress = $this->get_sitepress_mock( $wp_api_mock );
		$sitepress->method( 'get_setting' )->willReturnMap( array(
			                                                    array(
				                                                    'translation-management',
				                                                    array(),
				                                                    array( 'custom_fields_translation' => array( $custom_name => 1 ) )
			                                                    )
		                                                    ) );
		$sitepress->expects( $this->once() )->method( 'admin_notices' )->withAnyParameters();
		$subject = new WPML_Sync_Custom_Field_Note( $sitepress );
		$subject->print_sync_copy_custom_field_note( $source_lang, $translations );
		$subject->print_sync_copy_custom_field_note( 'foo', $translations );
	}
}