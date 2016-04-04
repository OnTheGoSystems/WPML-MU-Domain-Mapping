<?php

trait WPML_MS_UnitTestCase {
	/**
	 * @param bool $check_filter whether to assert that the pre_option_page on
	 * front filter is set up or not.
	 *
	 * @return int|WP_Error
	 */
	protected function create_and_check_new_blog( $check_filter = false ) {
		$user_id      = get_current_user_id();
		$title        = rand_str( 10 );
		$current_site = get_current_site();
		$domain       = 'example.com';
		$newdomain    = $domain . '.' . preg_replace( '|^www\.|', '',
				$current_site->domain );
		$path         = $title;
		remove_filter( 'query',
			array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$new_blog_id = wpmu_create_blog( $newdomain,
			$path,
			$title,
			$user_id,
			array( 'public' => 1 ),
			$current_site->id );

		$this->assertTrue( $new_blog_id != get_current_blog_id() );
		switch_to_blog( $new_blog_id );
		if ( $check_filter ) {
			$this->assertTrue( has_filter( 'pre_option_page_on_front' ) );
		}
		wp_cache_init();
		set_current_screen( 'dashboard' );
		$this->assertFalse( (bool) wpml_get_setting_filter( false,
			'setup_complete' ) );
		restore_current_blog();
		$found = false;
		wp_cache_get( $new_blog_id . 'icl_sitepress_settings',
			'sitepress_ms', false, $found );
		$this->assertTrue( $found );
		$this->assertTrue( (bool) wpml_get_setting_filter( false,
			'setup_complete' ) );

		return $new_blog_id;
	}

	abstract function assertTrue( $val, $message = '' );

	abstract function assertFalse( $val, $message = '' );
}