<?php

class Test_WPML_User_Options_Menu extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2402
	 */
	public function test_render() {
		foreach ( array( true, false ) as $is_admin ) {
			foreach (
				array(
					array( 'fr' ),
					array( 'de', 'fr' )
				) as $hidden_langs
			) {
				$wp_api_mock = $this->get_wp_api_mock();
				$wp_api_mock->method( 'current_user_can' )->willReturn( $is_admin );
				$sitepress = $this->get_sitepress_mock( $wp_api_mock );
				$sitepress->method( 'get_setting' )->willReturnMap( ( array(
					array(
						'admin_default_language',
						false,
						'_default_'
					),
					array( 'hidden_languages', false, $hidden_langs )
				) ) );
				$sitepress->method( 'get_languages' )->willReturn( array(
					'en' => array(
						'display_name' => 'English',
						'native_name'  => 'English Native Name',
						'active'       => 1
					),
					'de' => array(
						'display_name' => 'German',
						'native_name'  => 'German Native Name',
						'active'       => 1
					),
					'fr' => array(
						'display_name' => 'French',
						'native_name'  => 'French Native Name',
						'active'       => 0
					)
				) );

				/** @var WP_User $current_user */
				$current_user = (object) array( 'ID' => rand() );
				$subject      = new WPML_User_Options_Menu( $sitepress, $current_user );
				$dom          = new DOMDocument();
				$dom->loadHTML( $subject->render() );
				$user_admin_lang_select = $dom->getElementsByTagName( 'select' )->item( 0 );
				foreach ( $user_admin_lang_select->attributes as $attr => $val ) {
					if ( $attr === 'name' ) {
						$found_name_for_first_select = true;
						$this->assertEquals( 'icl_user_admin_language', (string) $val->value );
					}
				}
				$this->assertTrue( (bool) isset( $found_name_for_first_select ) && $found_name_for_first_select );
			}
		}
	}


	/**
	 * Test if hidden languages are displayed for users with 'translate' and/or 'manage_options' capability.
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2518
	 */
	public function test_hidden_options() {
		foreach ( array(
					  array( false, false ),
					  array( true, false ),
					  array( false, true ),
				  ) as $caps ) {
			$wp_api_mock = $this->get_wp_api_mock();

			// Add user caps.
			$wp_api_mock->method( 'current_user_can' )->willReturnMap(
				array(
					array( 'translate',      $caps[0] ),
					array( 'manage_options', $caps[1] ),
				)
			);
			$sitepress = $this->get_sitepress_mock( $wp_api_mock );
			$sitepress->method( 'get_setting' )->willReturnMap( ( array(
				array(
					'admin_default_language',
					false,
					'_default_',
				),
				array( 'hidden_languages', false, array() ),
			) ) );

			$sitepress->method( 'get_languages' )->willReturn( array(
				'en' => array(
					'display_name' => 'English',
					'native_name'  => 'English Native Name',
					'active'       => 1,
				),
			) );

			$current_user = (object) array( 'ID' => rand() );
			$subject      = new WPML_User_Options_Menu( $sitepress, $current_user );
			$dom          = new DOMDocument();
			$dom->loadHTML( $subject->render() );
			$user_display_hidden_languages = $dom->getElementsByTagName( 'input' );
			if ( $caps[0] || $caps[1] ) {
				$this->assertEquals( 'icl_show_hidden_languages', $user_display_hidden_languages->item(1)->attributes->item(0)->value );
			} else {
				$this->assertEquals( false, isset( $user_display_hidden_languages->item(1)->attributes[0]->value ) );
			}
		}
	}
}