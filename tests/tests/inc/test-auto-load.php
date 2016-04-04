<?php

class Test_WPML_Auto_Loader extends WPML_UnitTestCase {

	function test_auto_load_deep_directories() {
		$auto_load = WPML_Auto_Loader::get_instance();
		$auto_load->register( WPML_TEST_DIR . '/res/' );
		
		$base = new WPML_Auto_Load_Base();
		$this->assertEquals( 'WPML_Auto_Load_Base', $base->get_name() );
		$depth_1 = new WPML_Auto_Load_Depth_1();
		$this->assertEquals( 'WPML_Auto_Load_Depth_1', $depth_1->get_name() );
		$depth_2 = new WPML_Auto_Load_Depth_2();
		$this->assertEquals( 'WPML_Auto_Load_Depth_2', $depth_2->get_name() );
		$depth_3 = new WPML_Auto_Load_Depth_3();
		$this->assertEquals( 'WPML_Auto_Load_Depth_3', $depth_3->get_name() );
	}
}