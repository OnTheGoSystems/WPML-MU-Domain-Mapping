<?php

/**
 * Class WPML_MU_Domain_Mapping_Filters
 */
class WPML_MU_Domain_Mapping_Filters {

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var string */
	private $abs_home_url;

	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

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
				$domain             = $this->select_active_mapped_domain();
				$parsed_url         = wp_parse_url( get_option( 'home' ) );
				$this->abs_home_url = $domain ? trailingslashit( $parsed_url['scheme'] . '://' . $domain ) : $abs_home_url;
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
		return ! $this->sitepress->get_wp_api()->is_main_site()
			   && $this->sitepress->get_wp_api()->constant( 'DOMAIN_MAPPING' );
	}

	/** @return null|string */
	private function select_active_mapped_domain() {
		$domain = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT domain FROM {$this->wpdb->dmtable}
				 WHERE blog_id  = %d AND active = 1",
				$this->wpdb->blogid
			)
		);

		return $domain;
	}
}
