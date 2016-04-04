<?php

class Test_WPML_Post_Status extends WPML_UnitTestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_set_status_exception() {
		global $wpdb;

		$subject = new WPML_Post_Status( $wpdb );
		$subject->set_status( 0, 0 );
	}
}