<?php

class TestFunctions_Troubleshooting extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1155
	 */
	function test_icl_reset_wpml() {
		global $wpdb;

		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );

		$icl_tables = array(
			$wpdb->prefix . 'icl_languages',
			$wpdb->prefix . 'icl_languages_translations',
			$wpdb->prefix . 'icl_translations',
			$wpdb->prefix . 'icl_translation_status',
			$wpdb->prefix . 'icl_translate_job',
			$wpdb->prefix . 'icl_translate',
			$wpdb->prefix . 'icl_locale_map',
			$wpdb->prefix . 'icl_flags',
			$wpdb->prefix . 'icl_content_status',
			$wpdb->prefix . 'icl_core_status',
			$wpdb->prefix . 'icl_node',
			$wpdb->prefix . 'icl_strings',
			$wpdb->prefix . 'icl_translation_batches',
			$wpdb->prefix . 'icl_string_translations',
			$wpdb->prefix . 'icl_string_status',
			$wpdb->prefix . 'icl_string_positions',
			$wpdb->prefix . 'icl_message_status',
			$wpdb->prefix . 'icl_reminders',
		);

		foreach ( $icl_tables as $table_name ) {
			$this->assertNotNull(
				$wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ),
				"{$table_name} was not there before testing icl_reset_wpml"
			);
		}
		icl_reset_wpml();
		$icl_tables[ ] = $wpdb->prefix . 'icl_string_packages';
		foreach ( $icl_tables as $table_name ) {
			$this->assertNull(
				$wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ),
				"{$table_name} was not deleted by icl_reset_wpml"
			);
		}
		$this->assertFalse ( get_option ( 'icl_sitepress_settings' ) );
		wpml_test_install_setup();
	}
}

