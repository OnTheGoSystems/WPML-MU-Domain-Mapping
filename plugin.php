<?php
/*
Plugin Name: WPML MU Domain Mapping
Plugin URI: https://wpml.org/
Description: This is a bridge plugin to make <a href="https://wordpress.org/plugins/wordpress-mu-domain-mapping/" target="_blank">MU Domain Mapping</a> compatible with WPML
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 1.0.0
Plugin Slug: wpml-mu-domain-mapping
*/

function wpml_mu_domain_mapping_load() {
	global $wpdb, $sitepress;
	$wpml_auto_loader_instance = WPML_Auto_Loader::get_instance();
	$wpml_auto_loader_instance->register( dirname( __FILE__ ) . '/' );

	if ( $sitepress->get_wp_api()->constant( 'DOMAIN_MAPPING' )	) {
		$mu_domain_mapping_filters = new WPML_MU_Domain_Mapping_Filters( $wpdb, $sitepress );
		$mu_domain_mapping_filters->init_hooks();
	}
}
add_action( 'plugins_loaded', 'wpml_mu_domain_mapping_load' );