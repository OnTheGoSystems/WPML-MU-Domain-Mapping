<?php

class Test_WPML_ICL_Languages extends WPML_UnitTestCase {
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_constructor_wrong_code() {
		global $wpdb;

		new WPML_ICL_Languages( $wpdb, '', 'code' );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_constructor_wrong_locale() {
		global $wpdb;

		new WPML_ICL_Languages( $wpdb, '', 'default_locale' );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_constructor_wrong_type() {
		global $wpdb;

		new WPML_ICL_Languages( $wpdb, 'en', 'foooooo' );
	}

	public function test_exists() {
		global $wpdb;

		foreach (
			array(
				'en'      => array( 'code', true ),
				'foo'     => array( 'code', false ),
				'fr_Fr'   => array( 'default_locale', true ),
				'foo_Bar' => array( 'default_locale', false )
			) as $lang_code => $type_exists
		) {
			$subject = new WPML_ICL_Languages( $wpdb, $lang_code, $type_exists[0] );
			$this->assertEquals( $type_exists[1], $subject->exists() );
		}
	}
}