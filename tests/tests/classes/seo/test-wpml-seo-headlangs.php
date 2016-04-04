<?php

/**
 * Class Test_WPML_SEO_HeadLangs
 * @group seo
 */
class Test_WPML_SEO_HeadLangs extends WPML_UnitTestCase {
	/**
	 * Test head_langs function priority in wp_head action.
	 */
	public function test_head_langs_priority() {
		global $sitepress;
		$head_langs_priority_cases = array( 10, 1, false );

		/** @var bool $head_langs_case */
		foreach ( array( true, false ) as $head_langs_case ) {

			foreach ( $head_langs_priority_cases as $head_langs_priority_case ) {

				$assert_info_data = array(
					'head_langs_case'          => $head_langs_case,
					'head_langs_priority_case' => $head_langs_priority_case,
				);

				$assert_info = json_encode( $assert_info_data );

				$this->set_settings_case( $head_langs_case, $head_langs_priority_case );
				$expected_priority = $this->get_expected_priority( $head_langs_priority_case );

				$wpml_seo_headlangs = new WPML_SEO_HeadLangs( $sitepress );
				$wpml_seo_headlangs->init_hooks();

				$this->check_ui_selected_value( $wpml_seo_headlangs, $expected_priority, $assert_info );
				$this->check_wp_head_hook_priority( $head_langs_case, $expected_priority, $wpml_seo_headlangs, $assert_info );
			}
		}
	}

	/**
	 * @param WPML_SEO_HeadLangs $wpml_seo_headlangs
	 * @param int                $expected_priority
	 * @param string             $assert_info
	 */
	private function check_ui_selected_value( $wpml_seo_headlangs, $expected_priority, $assert_info ) {
		ob_start();
		$wpml_seo_headlangs->render_menu();
		$menu_content = ob_get_clean();

		$menu_dom = new DOMDocument();
		$menu_dom->loadHTML( $menu_content );
		$menu_xpath       = new DOMXPath( $menu_dom );
		$selected_options = $menu_xpath->query( '//option[@selected="selected"]' );

		$this->assertEquals( 1, $selected_options->length, $assert_info );
		/** @var DOMElement $selected_option */
		$selected_option = $selected_options->item( 0 );
		$this->assertEquals( $expected_priority, $selected_option->getAttribute( 'value' ), $assert_info );
	}

	/**
	 * @param bool               $head_langs_enabled
	 * @param int                $expected_priority
	 * @param WPML_SEO_HeadLangs $wpml_seo_headlangs
	 * @param string             $assert_info
	 */
	private function check_wp_head_hook_priority( $head_langs_enabled, $expected_priority, $wpml_seo_headlangs, $assert_info ) {
		if ( $head_langs_enabled ) {
			$this->assertEquals( $expected_priority, has_action( 'wp_head', array( $wpml_seo_headlangs, 'head_langs' ) ), $assert_info );
			remove_action( 'wp_head', array( $wpml_seo_headlangs, 'head_langs' ), $expected_priority );
			$this->assertEquals( false, has_action( 'wp_head', array( $wpml_seo_headlangs, 'head_langs' ) ), $assert_info );
		} else {
			$this->assertEquals( false, has_action( 'wp_head', array( $wpml_seo_headlangs, 'head_langs' ) ), $assert_info );
		}
	}

	/**
	 * @param int|false $head_langs_priority
	 *
	 * @return bool|int
	 */
	private function get_expected_priority( $head_langs_priority ) {
		return $head_langs_priority ? $head_langs_priority : 1 ;
	}

	/**
	 * @param bool      $head_langs_enabled
	 * @param int|false $head_langs_priority
	 */
	private function set_settings_case( $head_langs_enabled, $head_langs_priority ) {
		global $sitepress;
		$settings = array(
			'head_langs'                  => $head_langs_enabled,
			'canonicalization_duplicates' => 1
		);

		if ( $head_langs_priority ) {
			$settings['head_langs_priority'] = $head_langs_priority;
		} else {
			unset( $settings['head_langs_priority'] );
		}
		$sitepress->set_setting( 'seo', $settings, true );
	}
}