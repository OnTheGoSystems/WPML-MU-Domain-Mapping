<?php

class Test_WPML_Custom_Field_Setting_Factory extends WPML_UnitTestCase {

	public function test_get_post_meta_keys() {
		$this->check_passed_through( 'initial_custom_field_translate_states', 'get_post_meta_keys', true );
		$this->check_passed_through( 'initial_custom_field_translate_states', 'get_post_meta_keys', false );
	}

	public function test_get_term_meta_keys() {
		$this->check_passed_through( 'initial_term_custom_field_translate_states', 'get_term_meta_keys', true );
		$this->check_passed_through( 'initial_term_custom_field_translate_states', 'get_term_meta_keys', false );
	}

	/**
	 * Just checks and defines the aliasing between the settings factory and the
	 * core TM class.
	 *
	 * @param string $tm_function      function name on \TranslationManagement
	 * @param string $subject_function function name on \WPML_Custom_Field_Setting_Factory
	 * @param bool   $show_system_fields
	 */
	private function check_passed_through( $tm_function, $subject_function, $show_system_fields ) {
		$tm_mock = $this->get_core_tm_mock();
		$fields = array();

		$items_count = mt_rand(5,10);

		for($i = 0; $i < $items_count; $i++) {
			$fields[] = random_string(5);
		}
		for($i = 0; $i < $items_count; $i++) {
			$fields[] = '_' . random_string(5);
		}

		shuffle($fields);

		$expected_fields     = array();
		foreach ( $fields as $field ) {
			$is_system_field = '_' === substr( $field, 0, 1 );
			if ( ! $is_system_field || $show_system_fields ) {
				$expected_fields[] = $field;
			}
		}

		$tm_mock->method( $tm_function )->willReturn( $fields );
		$subject                     = new WPML_Custom_Field_Setting_Factory( $tm_mock );
		$subject->show_system_fields = $show_system_fields;

		$actual_fields = array_values(call_user_func( array( $subject, $subject_function ) ));

		$assert_info = json_encode( array( 'expected' => $expected_fields, 'actual' => $actual_fields ) );

		$this->assertEquals( $expected_fields, $actual_fields, $assert_info );
	}
}