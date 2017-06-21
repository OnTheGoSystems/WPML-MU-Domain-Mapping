<?php
define( 'WPML_MU_DOMAIN_MAPPING_TESTS_MAIN_FILE', __DIR__ . '/../../plugin.php' );
define( 'WPML_MU_DOMAIN_MAPPING_PATH', dirname( WPML_MU_DOMAIN_MAPPING_TESTS_MAIN_FILE ) );

$autoloader_dir = WPML_MU_DOMAIN_MAPPING_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', WPML_MU_DOMAIN_MAPPING_PATH . '/../../' );
}