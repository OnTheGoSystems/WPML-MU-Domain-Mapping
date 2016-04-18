<?php

class WPML_MU_Domain_Mapping_Requirements {

	private $missing_requirements = array();

	public function __construct() {
		add_action( 'wpml_loaded',   array( $this, 'wpml_loaded_action' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices_action' ) );

		if ( is_admin() && $this->is_plugin_active_for_network( WPML_MU_DOMAIN_MAPPING_ID ) ) {
			add_action( 'network_admin_notices', array( $this, 'admin_notices_action' ) );
		}
	}

	public function wpml_loaded_action() {
		$this->check_required_plugins();

		if ( empty( $this->missing_requirements ) ) {
			do_action( 'wpml_mu_domain_mapping_has_requirements' );
		}
	}

	private function check_required_plugins() {

		if ( ! defined( 'ICL_SITEPRESS_VERSION' )
		     || ICL_PLUGIN_INACTIVE
		     || version_compare( ICL_SITEPRESS_VERSION, '3.3.7', '<' )
		) {
			$this->missing_requirements['wpml-active'] = array(
				'message' => sprintf( __( 'The plugin <a href="%s">WPML</a> is not active.', 'wpml-mu-domain-mapping' ),
									  'https://wpml.org' ),
			);
		}

		if ( ! $this->is_plugin_active_for_network( 'wordpress-mu-domain-mapping/domain_mapping.php' ) ) {
			$this->missing_requirements['mu-domain-mapping-active'] = array(
				'message' => sprintf( __( 'The plugin <a href="%1$s">MU Domain Mapping</a> is not active on the network
										   Please review the installation process on <a href="%2$s">%2$s</a>', 'wpml-mu-domain-mapping' ),
					'https://wordpress.org/plugins/wordpress-mu-domain-mapping/',
					'https://wordpress.org/plugins/wordpress-mu-domain-mapping/installation/' ),
			);
		}
	}

	public function admin_notices_action() {
		if ( $this->missing_requirements ) {
			$missing_slugs_classes = implode( ' ', array_keys( $this->missing_requirements ) );

			$output = '<div class="message error wpml-admin-notice wpml-gfml-inactive ' . $missing_slugs_classes . '">' .
							'<p>' .
			                    __( 'WPML MU Domain Mapping is enabled but not effective.
										     Please check the missing requirements bellow:', 'wpml-mu-domain-mapping' ) .
							'</p>
							 <ul>';

			foreach ( $this->missing_requirements as $missing_requirement ) {
				$output .=      '<li>' . $missing_requirement['message'] . '</li>';
			}

			$output .=      '</ul>
				       </div>';

			echo $output;

		}
	}

	/**
	 * @param $plugin_id
	 *
	 * @return bool
	 */
	private function is_plugin_active_for_network( $plugin_id ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		return is_plugin_active_for_network( $plugin_id );
	}
}
