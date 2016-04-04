<?php

class Test_Plugins_Integration extends WPML_UnitTestCase {

	public function test_wpseo_filters_instantiation() {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			define( 'WPSEO_VERSION', '1.0.3' );
		}

		if ( version_compare( WPSEO_VERSION, '1.0.3', '>=' ) ) {
			remove_all_filters( 'wpml_translatable_user_meta_fields' );
			wpml_plugins_integration_setup();
			$filters = has_filter( 'wpml_translatable_user_meta_fields' );
			$this->assertTrue( $filters );
		}
	}
}