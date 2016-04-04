<?php

class Test_WPML_TM_Settings_Update extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1918
	 */
	function test_update_from_config() {
		/** @var TranslationManagement $mock_tm */
		$mock_tm           = $this->get_core_tm_mock();
		$mock_tm->settings = array(
			'taxonomies_readonly_config' => array( 'test_tax' => 1 )
		);
		/** @var SitePress $mock_sitepress */
		$mock_sitepress = $this->get_sitepress_mock();
		/** @var WPML_Settings_Helper $mock_settings_helper */
		$mock_settings_helper = $this->getMockBuilder( 'WPML_Settings_Helper' )->disableOriginalConstructor()->getMock();

		$subject = new WPML_TM_Settings_Update( 'taxonomy',
			'taxonomies',
			$mock_tm,
			$mock_sitepress,
			$mock_settings_helper );
		$subject->update_from_config( array() );
		$this->assertEmpty( $mock_tm->settings['taxonomies_readonly_config'] );
	}
}