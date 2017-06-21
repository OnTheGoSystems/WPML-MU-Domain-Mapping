<?php

abstract class WPML_MU_Domain_Mapping_UnitTestCase extends PHPUnit_Framework_TestCase {

	public function setUp() {
		parent::setUp();
		\WP_Mock::setUp();
	}

	public function tearDown() {
		\WP_Mock::tearDown();
		parent::tearDown();
	}
}
