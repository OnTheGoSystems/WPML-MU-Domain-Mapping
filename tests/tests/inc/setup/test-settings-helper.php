<?php
require_once ICL_PLUGIN_PATH . '/inc/setup/wpml-settings-helper.class.php';

class Test_WPML_Settings_Helper extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1868
	 */
	function test_update_cpt_sync_settings() {
		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_setting' )
		               ->will( $this->returnValueMap(
			               array(
				               array(
					               'custom_posts_sync_option',
					               array(),
					               array( 'cpt_1' => 1, 'cpt_2' => 1 )
				               )
			               )
		               ) );
		$pt_mock                 = $this->get_post_translation_mock();
		$subject                 = new WPML_Settings_Helper( $pt_mock, $sitepress_mock );
		$cpt_settings_save_count = did_action( 'wpml_save_cpt_sync_settings' );
		$new_settings            = array( 'cpt_1' => 0, 'cpt_3' => 1 );
		$updated_settings        = $subject->update_cpt_sync_settings( $new_settings );
		$this->assertEquals( $cpt_settings_save_count + 1, did_action( 'wpml_save_cpt_sync_settings' ) );
		$this->assertEquals( array( 'cpt_2' => 1, 'cpt_3' => 1 ), array_filter( $updated_settings ) );
	}
}