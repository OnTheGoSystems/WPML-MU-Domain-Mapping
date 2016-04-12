<?php

/**
 * Class WPML_MU_Domain_Mapping_Filters
 */
Class WPML_MU_Domain_Mapping_Filters extends WPML_WPDB_And_SP_User {

	/**
	 * @var string
	 */
	private $abs_home_url;

	public function init_hooks() {
		add_filter( 'wpml_url_converter_get_abs_home', array( $this, 'url_converter_get_abs_home_filter' ) );
	}

	/**
	 * @param string $abs_home_url
	 *
	 * @return string
	 */
	public function url_converter_get_abs_home_filter( $abs_home_url ) {

		if( ! isset( $this->abs_home_url ) ){

			if ( $this->is_plugin_operating() ) {
				$domain = $this->wpdb->get_var(
					$this->wpdb->prepare(
						"SELECT domain FROM {$this->wpdb->dmtable}
					 WHERE blog_id  = %d
					 LIMIT 1",
						$this->wpdb->blogid
					)
				);

				$protocol = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
				$this->abs_home_url = $domain ? trailingslashit( $protocol . $domain ) : $abs_home_url;
			} else {
				$this->abs_home_url = $abs_home_url;
			}
		}

		return $this->abs_home_url;
	}

	/**
	 * @return bool
	 */
	private function is_plugin_operating() {
		return $this->sitepress->get_wp_api()->constant( 'DOMAIN_MAPPING' )
		       && ! $this->sitepress->get_wp_api()->is_main_site()
		       && array_key_exists(
			       'wordpress-mu-domain-mapping/domain_mapping.php',
			       apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins' ) )
		       );
	}
}