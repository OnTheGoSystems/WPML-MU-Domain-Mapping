<?php

class WPML_Test_Functions extends WPML_UnitTestCase {

	function test_icl_sitepress_activate() {
		global $wpdb;

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
		icl_sitepress_activate();
		foreach ( $icl_tables as $table_name ) {
			$this->assertTrue(
				0 === strcasecmp( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ), $table_name )
				,
				"{$table_name} was not created by icl_sitepress_activate" );
		}
	}

	function test_wpml_strip_subdir_from_url() {
		global $wpml_url_converter, $sitepress_settings;

		$new_home = $this->set_subdir_path();

		$wpml_url_converter = load_wpml_url_converter( $sitepress_settings,
			2,
			icl_get_setting( 'default_language' ) );
		$this->assertEquals( 'http://example.com',
			wpml_strip_subdir_from_url( $new_home ) );
		$this->assertEquals( 'example.com',
			wpml_strip_subdir_from_url( str_replace( 'http://', '',
				$new_home ) ) );
		$this->assertEquals( 'example.com/foo/bar',
			wpml_strip_subdir_from_url( str_replace( 'http://', '',
					$new_home ) . '/foo/bar' ) );
		$this->assertEquals( 'http://example.com/foo/bar',
			wpml_strip_subdir_from_url( $new_home . '/foo/bar' ) );
		$this->assertEquals( '/foo/bar',
			wpml_strip_subdir_from_url( '/testslug/foo/bar' ) );
	}

	function test_wpml_validate_host() {
		global $wpml_url_converter;

		$new_home = $this->set_subdir_path();

		$this->assertEquals(
			'<!--' . $new_home . '/fr' . '-->',
			wpml_validate_host( '/fr/?foo=bar', $wpml_url_converter, true )
		);
		$this->assertEquals(
			'<!--' . $new_home . '-->',
			wpml_validate_host( '/fr/?foo=bar', $wpml_url_converter, false )
		);
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1531
	 */
	function test_wpml_prepare_in() {
		$this->assertFalse( (bool) wpml_prepare_in( null ) );
		$this->assertFalse( (bool) wpml_prepare_in( array() ) );
	}

	/**
	 * At least check that the nonce field is generated according to the correct
	 * naming scheme.
	 */
	function test_wpml_nonce_field() {
		$action      = rand_str( 10 );
		$nonce_field = wpml_nonce_field( $action );
		$this->assertNotFalse( strpos( $nonce_field, wp_create_nonce( $action . '_nonce' ) ) );
		$this->assertNotFalse( strpos( $nonce_field, 'name="_icl_nonce"' ) );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1861
	 */
	function test_wpml_make_post_duplicates_action() {
		global $sitepress;
		$expected_count = count( $sitepress->get_active_languages() ) - 1;
		foreach ( array( 'auto-draft', 'publish', 'private', 'draft', 'future' ) as $post_status ) {
			$post_array['post_content']  = 'Dummy Content';
			$post_array['post_title']    = 'Dummy Title';
			$post_array['post_excerpt']  = 'Dummy Content';
			$post_array['post_status']   = $post_status;

			if ( 'future' === $post_status ) {
				$post_array['post_date']     = date( 'Y-m-d H:i:59', strtotime( '+7 day' ) );
				$post_array['post_date_gmt'] = gmdate( 'Y-m-d H:i:59', strtotime( '+7 day' ) );
			}

			$id = wp_insert_post( $post_array );
			$sitepress->set_element_language_details( $id, 'post_post', false, 'en' );
			wpml_make_post_duplicates_action( $id );
			$args = array(
				'post_status' => $post_status,
				'meta_query'  => array(
					array(
						'key'     => '_icl_lang_duplicate_of',
						'value'   => $id,
						'compare' => '=',
					),
				),
			);

			$duplicates = new WP_Query( $args );
			if ( 'auto-draft' === $post_status ) {
				$this->assertEquals( 0, $duplicates->found_posts );
			} else {
				$this->assertEquals( $expected_count, $duplicates->found_posts );
			}

			while ( $duplicates->have_posts() ) {
				$duplicates->the_post();
				$this->assertEquals( $id, get_post_meta( get_the_ID(), '_icl_lang_duplicate_of', true ) );
			}
			wp_reset_postdata();
		}
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Content, title, and excerpt are empty.
	 */
	public function test_wpml_make_post_duplicates_action_with_exception() {
		global $sitepress;
		$expected_count = count( $sitepress->get_active_languages() ) - 1;
		$post_array['post_content']  = 'Dummy Content';
		$post_array['post_title']    = 'Dummy Title';
		$post_array['post_excerpt']  = 'Dummy Content';
		$post_array['post_status']   = 'publish';
		$post_array['post_type']   = 'page';
		$id = wp_insert_post( $post_array );
		$sitepress->set_element_language_details( $id, 'post_post', false, 'en' );
		add_filter( 'wp_insert_post_empty_content', '__return_true' );
		wpml_make_post_duplicates_action( $id );
	}
}
