<?php

class Test_Cache extends WPML_UnitTestCase {

	private $cache_cleared = false;

	function test_cache_reload() {
		add_action( 'wpml_cache_clear', array( $this, 'clear_cache_action' ) );
		icl_cache_clear();
		$this->assertTrue( $this->cache_cleared );
	}

	function clear_cache_action() {
		$this->cache_cleared = true;
	}
}