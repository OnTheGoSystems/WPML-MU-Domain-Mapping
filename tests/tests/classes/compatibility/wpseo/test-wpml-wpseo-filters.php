<?php

class Test_WPML_WPSEO_Filters extends WPML_UnitTestCase {

	public function test_init_hooks() {
		$subject = new WPML_WPSEO_Filters();
		$subject->init_hooks();

		$priority = has_filter( 'wpml_translatable_user_meta_fields', array( $subject, 'wpml_translatable_user_meta_fields_filter' ) );
		$this->assertEquals( 10, $priority );
	}

	public function test_wpml_translatable_user_meta_fields_filter() {
		$default_fields = array(
			'first_name',
			'last_name',
			'nickname',
			'description',
			'display_name',
		);

		$subject      = new WPML_WPSEO_Filters();
		$wpseo_fields = $subject->get_user_meta_fields();
		$all_fields   = $subject->wpml_translatable_user_meta_fields_filter( $default_fields );
		foreach ( $wpseo_fields as $wpseo_field ) {
			$this->assertTrue( in_array( $wpseo_field, $all_fields) );
		}
	}
}