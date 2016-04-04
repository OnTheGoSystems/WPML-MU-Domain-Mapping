<?php

class Test_WPML_TM_Settings_Post_Process extends WPML_UnitTestCase {

	public function test_run() {
		$this->check_no_settings_case();
		$this->check_all_updated_case();
	}

	/**
	 * Checks that settings are saved to the wp_options in case they changed
	 * from their previous values
	 */
	private function check_all_updated_case() {
		$tm_mock           = $this->get_core_tm_mock();
		$tm_mock->settings = array(
			WPML_POST_TYPE_READONLY_SETTING_INDEX                  => array( 'cpt' => WPML_TRANSLATE_CUSTOM_FIELD ),
			'__' . WPML_POST_TYPE_READONLY_SETTING_INDEX . '_prev' => array( 'other_cpt' => WPML_IGNORE_CUSTOM_FIELD )
		);
		$tm_mock->expects( $this->once() )->method( 'save_settings' );
		$subject = new WPML_TM_Settings_Post_Process( $tm_mock );
		$subject->run();
	}

	/**
	 * Checks that no saving takes place in the absence of any changes/settings
	 */
	private function check_no_settings_case() {
		$tm_mock = $this->get_core_tm_mock();
		$tm_mock->expects( $this->never() )->method( 'save_settings' );
		$subject = new WPML_TM_Settings_Post_Process( $tm_mock );
		$subject->run();
	}
}