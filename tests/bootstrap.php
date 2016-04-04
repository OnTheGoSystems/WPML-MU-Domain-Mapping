<?php

if ( ! defined( 'WPML_CORE_PATH' ) ) {
	define( 'WPML_CORE_PATH', dirname( __FILE__ ) . '/../../sitepress-multilingual-cms' );
}

define( 'WPML_MU_DOMAIN_MAPPING_TEST_DIR', dirname( __FILE__ ) );
$_tests_dir = 'wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require WPML_CORE_PATH . '/sitepress.php';
	require WPML_CORE_PATH . '/tests/util/functions.php';
	require WPML_MU_DOMAIN_MAPPING_TEST_DIR . '/../plugin.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
require WPML_CORE_PATH . '/tests/util/wpml-unittestcase.class.php';
require WPML_CORE_PATH . '/tests/tests/network/test-wpml-ms-unit-test-case.php';
require dirname( __FILE__ ) . '/util/wpml-mu-domain-mapping-unittestcase.class.php';