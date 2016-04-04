<?php

class Test_WPML_TM_Post_Edit_Custom_Field_Settings_Menu extends WPML_UnitTestCase {

	public function test_render() {

		$post          = (object) array( 'ID' => 1 );
		$post_no_meta  = (object) array( 'ID' => 5 );
		$wp_api_mock   = $this->get_wp_api_mock();
		$custom_fields = array(
			'field_a' => array( WPML_TRANSLATE_CUSTOM_FIELD, true ),
			'field_b' => array( WPML_COPY_CUSTOM_FIELD, false ),
			'field_c' => array( WPML_IGNORE_CUSTOM_FIELD, false ),
			'field_d' => array( WPML_IGNORE_CUSTOM_FIELD, true )
		);

		$wp_api_mock->method( 'get_post_custom_keys' )->willReturnMap( array(
			array(
				$post->ID,
				array_keys( $custom_fields )
			),
			array(
				$post_no_meta->ID,
				array()
			)
		) );
		$sitepress       = $this->get_sitepress_mock( $wp_api_mock );
		$setting_factory = $this->get_cf_settings_factory_mock();
		$setting_factory->method( 'post_meta_setting' )->willReturnMap(
			array(
				array(
					'field_a',
					$this->get_cf_mock( $custom_fields['field_a'][0], $custom_fields['field_a'][1] )
				),
				array(
					'field_b',
					$this->get_cf_mock( $custom_fields['field_b'][0], $custom_fields['field_b'][1] )
				),
				array(
					'field_c',
					$this->get_cf_mock( $custom_fields['field_c'][0], $custom_fields['field_c'][1] )
				),
				array(
					'field_d',
					$this->get_cf_mock( $custom_fields['field_d'][0], $custom_fields['field_d'][1] )
				)
			)
		);
		$setting_factory->method( 'filter_custom_field_keys' )->willReturn( array_keys( $custom_fields ) );
		$subject = new WPML_TM_Post_Edit_Custom_Field_Settings_Menu( $sitepress, $setting_factory, $post );
		$dom     = new DOMDocument();
		$dom->loadHTML( $subject->render() );
		$this->assertEquals( 'table', $dom->getElementsByTagName( 'table' )->item( 0 )->tagName );
		$this->assertTrue( $subject->is_rendered() );
		$cf_names = array_keys($custom_fields);
		foreach ( $cf_names as $cf_name ) {
			$this->assertEquals( $custom_fields[ $cf_name ][0] != WPML_IGNORE_CUSTOM_FIELD || ! $custom_fields[ $cf_name ][1],
				(bool) $dom->getElementById( 'icl_mcs_cf_' . base64_encode( $cf_name ) ),
				"Field {$cf_name} was not properly hidden or displayed!" );
		}

		/** @var WP_Post $post_no_meta */
		$sitepress       = $this->get_sitepress_mock( $wp_api_mock );
		$setting_factory = $this->get_cf_settings_factory_mock();
		$setting_factory->method( 'filter_custom_field_keys' )->willReturn( array() );
		$subject = new WPML_TM_Post_Edit_Custom_Field_Settings_Menu( $sitepress, $setting_factory, $post_no_meta );
		$this->assertEquals( '', $subject->render() );
		$this->assertFalse( $subject->is_rendered() );
	}

	private function get_cf_mock( $status, $read_only ) {
		$cf_mock = $this->getMockBuilder( 'WPML_Post_Custom_Field_Setting' )
		                ->disableOriginalConstructor()
		                ->getMock();
		$cf_mock->method( 'status' )->willReturn( $status );
		$cf_mock->method( 'read_only' )->willReturn( $read_only );
		$cf_mock->method( 'excluded' )->willReturn( $status === WPML_IGNORE_CUSTOM_FIELD && $read_only );

		return $cf_mock;
	}
}