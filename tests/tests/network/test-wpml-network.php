<?php
if ( defined( 'WP_TESTS_MULTISITE' ) && WP_TESTS_MULTISITE ) {

	class Test_WPML_Network extends WPML_UnitTestCase {
		use WPML_MS_UnitTestCase;

		/**
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1732
		 */
		function test_activate_new_site() {
			global $sitepress;

			add_filter( 'pre_option_page_on_front',
				array( $sitepress, 'pre_option_page_on_front' ), 10, 2 );
			add_filter( 'pre_option_page_for_posts',
				array( $sitepress, 'pre_option_page_for_posts' ), 10, 2 );
			set_current_screen( 'network' );
			for ( $i = 1; $i < 3; $i ++ ) {
				$this->create_and_check_new_blog( true );
			}
		}
	}
}