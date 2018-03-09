<?php
/*
Plugin Name: WPML MU Domain Mapping
Plugin URI: https://wpml.org/
Description: This is a bridge plugin to make <a href="https://wordpress.org/plugins/wordpress-mu-domain-mapping/" target="_blank">MU Domain Mapping</a> compatible with WPML. It also works with <a href="https://premium.wpmudev.org/project/domain-mapping/">Domain Mapping</a>.
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 1.1.1
Plugin Slug: wpml-mu-domain-mapping
*/

if ( defined( 'WPML_MU_DOMAIN_MAPPING_PATH' ) || get_option( '_wpml_inactive' ) ) {
	return;
}

define( 'WPML_MU_DOMAIN_MAPPING_ID', plugin_basename( __FILE__ ) );
define( 'WPML_MU_DOMAIN_MAPPING_PATH', dirname( __FILE__ ) );

require_once 'classes/class-wpml-mu-domain-mapping-requirements.php';
new WPML_MU_Domain_Mapping_Requirements();

function wpml_mu_domain_mapping_enable() {
	global $wpdb, $sitepress;

	require_once 'classes/class-wpml-mu-domain-mapping-filters.php';
	$mu_domain_mapping_filters = new WPML_MU_Domain_Mapping_Filters( $wpdb, $sitepress );
	$mu_domain_mapping_filters->init_hooks();
}
add_action( 'wpml_mu_domain_mapping_has_requirements', 'wpml_mu_domain_mapping_enable' );
