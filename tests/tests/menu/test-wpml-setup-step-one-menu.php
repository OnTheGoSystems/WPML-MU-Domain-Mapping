<?php

class Test_WPML_Setup_Step_One_Menu extends WPML_UnitTestCase {

	public function test_render() {
		global $sitepress;

		$this->check_renders( $sitepress );
		$this->check_specific_cases();
	}

	private function check_specific_cases() {
		global $wpdb;

		$wp_api_option_set = $this->get_wp_api_mock();
		$wp_api_option_set->method( 'get_option' )->willReturnMap( array(
			array(
				'WPLANG',
				false,
				'de_DE'
			)
		) );
		$wp_api_constant_set = $this->get_wp_api_mock();
		$wp_api_constant_set->method( 'constant' )->willReturnMap( array(
			array(
				'WP_LANG',
				'de_DE'
			)
		) );
		$wp_api_non_default_constant_set = $this->get_wp_api_mock();
		$wp_api_non_default_constant_set->method( 'constant' )->willReturnMap( array(
			array(
				'WP_LANG',
				'de_AT'
			)
		) );
		foreach (
			array(
				$wp_api_option_set,
				$wp_api_constant_set,
				$wp_api_non_default_constant_set
			) as $wp_api
		) {
			$sitepress  = $this->get_sitepress_mock( $wp_api );
			$wp_records = new WPML_Records( $wpdb );
			$sitepress->method( 'get_records' )->willReturn( $wp_records );
			$sitepress->method( 'get_languages' )->with( 'de', false, false )->willReturn( array(
				'de' => array(
					'code'         => 'de',
					'display_name' => 'German'
				),
				'en' => array(
					'code'         => 'en',
					'display_name' => 'English'
				)
			) );
			$this->check_renders( $sitepress );
		}
	}

	private function check_renders( $sitepress ) {
		$subject = new WPML_Setup_Step_One_Menu( $sitepress );
		$dom     = new DOMDocument();
		$dom->loadHTML( $subject->render() );
		$this->assertTrue( (bool) $dom->getElementById( 'icl_initial_language' ) );
	}
}