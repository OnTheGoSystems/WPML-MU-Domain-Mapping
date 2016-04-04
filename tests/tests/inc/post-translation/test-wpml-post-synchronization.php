<?php

class Test_WPML_Post_Synchronization extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2624
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1074
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1573
	 */
	public function test_sync_with_translations() {
		global $wpml_post_translations, $sitepress, $wpdb;
		$def_lang = wpml_get_setting_filter( false, 'default_language' );
		$orig     = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );

		foreach ( array( 'draft', 'pending', 'future', 'private' ) as $post_status ) {
			$date = date( 'Y-m-d H:i:59', strtotime( '+7 day' ) );
			$date_gmt = gmdate( 'Y-m-d H:i:59', strtotime( '+7 day' ) );
			$args = array(
				'post_date'     => $date,
				'post_date_gmt' => $date_gmt,
				'ID'            => $orig,
			);
			wp_update_post( $args );

			$trid       = $wpml_post_translations->get_element_trid( $orig );
			wpml_test_insert_post( 'fr', 'post', $trid, rand_str() );
			$translations = $wpml_post_translations->get_element_translations( false, $trid );
			$second_id = $translations['fr'];

			$settings['sync_post_date'] = true;
			$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );
			$sync->sync_with_translations( $orig );

			$synced_post = $wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE ID = $second_id", ARRAY_A );
			$this->assertEquals( 'future', $synced_post['post_status'] );
			$this->assertEquals( $date, $synced_post['post_date'] );
			$this->assertEquals( $date_gmt, $synced_post['post_date_gmt'] );
			wp_update_post( array(
				'ID'          => $second_id,
				'post_status' => $post_status,
			) );

			$settings['sync_post_date'] = true;
			$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );
			$sync->sync_with_translations( $orig );
			$synced_post = $wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE ID = $second_id", ARRAY_A );
			$this->assertEquals( $post_status, $synced_post['post_status'] );
			$this->assertEquals( $date, $synced_post['post_date'] );
			$this->assertEquals( $date_gmt, $synced_post['post_date_gmt'] );
		}

		$date = date( 'Y-m-d H:i:59', strtotime( '-7 day' ) );
		$date_gmt = gmdate( 'Y-m-d H:i:59', strtotime( '-7 day' ) );
		foreach ( array(
			array( 'draft', 'publish' ),
			array( 'publish', 'draft' ),
			array( 'publish', 'auto-draft' ),
		) as
			$post_status
		) {
			$args = array(
				'post_date'     => $date,
				'post_date_gmt' => $date_gmt,
				'post_status'   => $post_status[0],
				'ID'            => $orig,
			);
			wp_update_post( $args );

			$args = array(
				'post_status'   => $post_status[1],
				'ID'            => $second_id,
			);
			wp_update_post( $args );

			$sync->sync_with_translations( $orig );
			$synced_post = $wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE ID = $second_id", ARRAY_A );

			$this->assertEquals( $date, $synced_post['post_date'] );
			$this->assertEquals( $date_gmt, $synced_post['post_date_gmt'] );
			$this->assertEquals( $post_status[1], $synced_post['post_status'] );
		}
	}

	public function test_sync_private_flag() {
		global $wpml_post_translations, $sitepress;
		$def_lang = wpml_get_setting_filter( false, 'default_language' );
		$orig     = wpml_test_insert_post( $def_lang, 'post', false, rand_str() );

		foreach ( array( true, false ) as $sync_post_date ) {
			$date = date( 'Y-m-d H:i:59', strtotime( '-7 day' ) );
			$date_gmt = gmdate( 'Y-m-d H:i:59', strtotime( '-7 day' ) );
			wp_update_post( array(
				'post_status'   => 'private',
				'post_date'     => $date,
				'post_date_gmt' => $date_gmt,
				'ID'            => $orig,
			) );

			$trid       = $wpml_post_translations->get_element_trid( $orig );
			wpml_test_insert_post( 'fr', 'post', $trid, rand_str() );
			$translations = $wpml_post_translations->get_element_translations( false, $trid );
			$second_id = $translations['fr'];

			$settings['sync_post_date'] = $sync_post_date;
			$settings['sync_private_flag'] = true;
			$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );
			$sync->sync_with_translations( $orig );
			wp_cache_init();
			$synced_post = get_post( $second_id, ARRAY_A );
			$this->assertEquals( 'private', $synced_post['post_status'] );
			if ( $sync_post_date ) {
				$this->assertEquals( $date, $synced_post['post_date'] );
				$this->assertEquals( $date_gmt, $synced_post['post_date_gmt'] );
			}
		}
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1034
	 */
	public function test_post_sync_non_default_translation() {
		global $wpml_post_translations, $sitepress;
		$def_lang = wpml_get_setting_filter( false, 'default_language' );
		$orig     = wpml_test_insert_post( 'fr', 'post', false, rand_str() );

		$date = date( 'Y-m-d H:i:59', strtotime( '+7 day' ) );
		$date_gmt = gmdate( 'Y-m-d H:i:59', strtotime( '+7 day' ) );
		$args = array(
			'post_date'     => $date,
			'post_date_gmt' => $date_gmt,
			'ID'            => $orig,
		);
		wp_update_post( $args );

		$trid       = $wpml_post_translations->get_element_trid( $orig );
		wpml_test_insert_post( $def_lang, 'post', $trid, rand_str() );
		$translations = $wpml_post_translations->get_element_translations( false, $trid );
		$second_id = $translations[ $def_lang ];

		$settings['sync_post_date'] = true;
		$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );
		$sync->sync_with_translations( $orig );
		wp_cache_init();
		$synced_post = get_post( $second_id, ARRAY_A );
		$this->assertEquals( $date, $synced_post['post_date'] );
		$this->assertEquals( $date_gmt, $synced_post['post_date_gmt'] );
	}

	public function test_fields_synchronization() {
		global $wpml_post_translations, $sitepress;
		foreach ( array( 'post', 'page' ) as $post_type ) {
			$def_lang = wpml_get_setting_filter( false, 'default_language' );
			$settings = array();


			$args = array(
				'menu_order'     => 0,
				'comment_status' => 'open',
				'ping_status'    => 'open',
			);

			foreach (
				array(
					'sync_page_template',
					'sync_comment_status',
					'sync_ping_status',
					'sync_private_flag',
					'sync_post_format',
					'sync_post_date',
					'sync_page_ordering',
					'sync_post_format',
				)
				as $enabled_option
			) {
				$settings[ $enabled_option ] = true;
			}

			$orig       = wpml_test_insert_post( $def_lang, $post_type, false, 'First page', '0', false, false );
			$args['ID'] = $orig;
			wp_update_post( $args );
			$second_id = wpml_test_insert_post( 'fr', 'page', $wpml_post_translations->get_element_trid( $orig ), 'First page translations' );

			$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );
			$sync->sync_with_translations( $orig );
			wp_cache_init();
			$synced_post = get_post( $second_id, ARRAY_A );
			$this->assertEquals( 0, $synced_post['menu_order'] );
			$this->assertEquals( 'open', $synced_post['comment_status'] );
			$this->assertEquals( 'open', $synced_post['ping_status'] );
			$this->assertEquals( 'First page translations', $synced_post['post_title'] );

			$date     = date( 'Y-m-d H:i:59', strtotime( '-2 day' ) );
			$date_gmt = gmdate( 'Y-m-d H:i:59', strtotime( '-2 day' ) );
			$args = array(
				'ID'             => $orig,
				'post_status'    => 'private',
				'post_date'      => $date,
				'post_date_gmt'  => $date_gmt,
				'meta_input'     => array( '_wp_page_template' => 'full-width.php' ),
				'menu_order'     => 5,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			);

			wp_update_post( $args );

			if ( 'post' === $post_type ) {
				set_post_format( $orig, 'gallery' );
			}

			$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );
			$sync->sync_with_translations( $orig );
			wp_cache_init();
			$synced_post = get_post( $second_id, ARRAY_A );
			$this->assertEquals( 'closed', $synced_post['comment_status'] );
			$this->assertEquals( 'closed', $synced_post['ping_status'] );
			$this->assertEquals( $date, $synced_post['post_date'] );
			$this->assertEquals( $date_gmt, $synced_post['post_date_gmt'] );
			$this->assertEquals( 5, $synced_post['menu_order'] );
			$this->assertEquals( 'First page translations', $synced_post['post_title'] );

			if ( 'page' === $post_type ) {
				$this->assertEquals( 'full-width.php', get_post_meta( $second_id, '_wp_page_template', true ) );
			} else {
				$format = wp_get_post_terms( $second_id, 'post_format' );
				$this->assertEquals( 'post-format-gallery', $format[0]->slug );
			}
		}
	}

	public function test_page_hierarchy_synchronization() {
		global $wpml_post_translations, $sitepress;
		foreach ( array( 'en' => 'pl', 'pl' => 'en' ) as $def_lang => $tr_lang ) {
			$settings['sync_page_parent'] = true;

			$page_1 = wpml_test_insert_post( $def_lang, 'page', false, 'Page 1', '0' );
			$page_2 = wpml_test_insert_post( $def_lang, 'page', false, 'Page 2', $page_1 );
			$page_3 = wpml_test_insert_post( $def_lang, 'page', false, 'Page 3', $page_2 );

			// Translate Page 3 into polish, this polish page should have parent set to No parent. save it
			$page_3_fr_id = wpml_test_insert_post( $tr_lang, 'page', $wpml_post_translations->get_element_trid( $page_3 ) );
			$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );
			$sync->sync_with_translations( $page_3 );

			// Display list of english pages. Structure should be still the same.
			$this->check_page_hierarchy( array( 0, $page_1, $page_2 ), array( $page_3_fr_id, $page_2, $page_3 ) );

			// Create translations for Page 1, Page 2
			$page_1_fr_id = wpml_test_insert_post( $tr_lang, 'page', $wpml_post_translations->get_element_trid( $page_1 ) );
			$page_2_fr_id = wpml_test_insert_post( $tr_lang, 'page', $wpml_post_translations->get_element_trid( $page_2 ) );

			$sync->sync_with_translations( $page_2 );

			$this->check_page_hierarchy( array( 0, $page_1_fr_id, $page_2_fr_id ), array( $page_1_fr_id, $page_2_fr_id, $page_3_fr_id ) );

			// Make a Quick edit for Page 3 in english. dont change anything, just save it.
			wp_update_post(
				array(
					'ID'        => $page_3,
					'post_name' => 'test',
				)
			);
			$sync->sync_with_translations( $page_3 );

			// Check Polish pages.
			$this->check_page_hierarchy( array( 0, $page_1_fr_id, $page_2_fr_id ), array( $page_1_fr_id, $page_2_fr_id, $page_3_fr_id ) );

			// Edit Page 3 in polish and change its parent to 'Page 1'.
			wp_update_post(
				array(
					'ID'          => $page_3_fr_id,
					'post_parent' => $page_1_fr_id,
				)
			);
			$sync->sync_with_translations( $page_3_fr_id );

			// Check Polish pages.
			$this->check_page_hierarchy( array( 0, $page_1_fr_id, $page_2_fr_id ), array( $page_1_fr_id, $page_2_fr_id, $page_3_fr_id ) );

			// Display list of english pages. Structure should be still the same.
			$this->check_page_hierarchy( array( 0, $page_1, $page_2 ), array( $page_1, $page_2, $page_3 ) );
		}
	}

	public function test_post_parent() {
		global $wpml_post_translations, $sitepress;
		$def_lang = wpml_get_setting_filter( false, 'default_language' );
		$target_lang = 'es';
		$settings = array();
		foreach (
			array(
				'sync_page_template',
				'sync_comment_status',
				'sync_ping_status',
				'sync_private_flag',
				'sync_post_format',
				'sync_page_parent',
			)
			as $enabled_option
		) {
			$settings[ $enabled_option ] = true;
		}

		// Add Pages: Page 1, Page 2, Page 3, Page 4 and translations Page 1 SP, Page 2 SP, Page 3 SP, Page 4 SP
		$page_1 = wpml_test_insert_post( $def_lang, 'page', false, 'Page 1', '0' );
		$page_2 = wpml_test_insert_post( $def_lang, 'page', false, 'Page 2', $page_1 );
		$page_3 = wpml_test_insert_post( $def_lang, 'page', false, 'Page 3', $page_2 );
		$page_4 = wpml_test_insert_post( $def_lang, 'page', false, 'Page 4', $page_3 );

		$page_1_es = wpml_test_insert_post( $target_lang, 'page', $wpml_post_translations->get_element_trid( $page_1 ) );
		$page_2_es = wpml_test_insert_post( $target_lang, 'page', $wpml_post_translations->get_element_trid( $page_2 ) );
		$page_3_es = wpml_test_insert_post( $target_lang, 'page', $wpml_post_translations->get_element_trid( $page_3 ) );
		$page_4_es = wpml_test_insert_post( $target_lang, 'page', $wpml_post_translations->get_element_trid( $page_4 ) );


		$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );


		$testcases = array(
			array(
				'parent' => $page_1,
				'ID'     => $page_2,
				'check'  => array(
					'expected' => array( $page_1_es ),
					'post_ids' => array( $page_2_es ),
				),
			),
			array(
				'parent' => $page_3_es,
				'ID'     => $page_2_es,
				'check'  => array(
					'expected' => array( $page_1 ),
					'post_ids' => array( $page_2 ),
				),
			),
			array(
				'parent' => $page_2,
				'ID'     => $page_4,
				'check'  => array(
					'expected' => array( $page_2_es ),
					'post_ids' => array( $page_4_es ),
				),
			),
			array(
				'parent' => $page_1,
				'ID'     => $page_4,
				'check'  => array(
					'expected' => array( $page_1_es ),
					'post_ids' => array( $page_4_es ),
				),
			),
			array(
				'parent' => $page_4,
				'ID'     => $page_3,
				'check'  => array(
					'expected' => array( $page_4_es ),
					'post_ids' => array( $page_3_es ),
				),
			),
			array(
				'parent' => $page_3,
				'ID'     => $page_2,
				'check'  => array(
					'expected' => array( '0', $page_3, $page_4, $page_1 ),
					'post_ids' => array( $page_1, $page_2, $page_3, $page_4 ),
				),
			),
			array(
				'parent' => '0',
				'ID'     => $page_2,
				'check'  => array(
					'expected' => array( '0' ),
					'post_ids' => array( $page_2_es ),
				),
			),
		);

		$this->update_parent_and_check_hierarchy( $testcases, $sync );

		/**
		 * Add translation of "Page 5" with title "Page 5 SP"
		 * Parent should be set by default to "Page 2 SP"
		 */
		$page_5 = wpml_test_insert_post( $def_lang, 'page', false, 'Page 5', $page_2 );
		$page_5_es = wpml_test_insert_post( $target_lang, 'page', $wpml_post_translations->get_element_trid( $page_5 ) );
		$sync->sync_with_translations( $page_5 );

		$this->check_page_hierarchy( array( $page_2_es ), array( $page_5_es ) );

		/**
		 * Remove "Page 2" (also from trash)
		 * Open "Page 5"
		 * Parent should be set to "(no parent)"
		 * Open "Page 5 SP"
		 * Parent should be set to "(no parent)"
		 */
		wp_delete_post( $page_2, true );
		$sync->sync_with_translations( $page_5 );
		$this->check_page_hierarchy( array( '0', '0' ), array( $page_5, $page_5_es ) );

		/**
		 * Add translation of "Page 2 SP" with title "Page 2"
		 * Publish
		 * Parent should be set to "(no parent)"
		 */
		$page_2_es = wpml_test_insert_post( $target_lang, 'page', $wpml_post_translations->get_element_trid( $page_2 ) );
		$this->check_page_hierarchy( array( '0' ), array( $page_2_es ) );

		/**
		 * Remove "Page 4" (also from trash)
		 * Open "Page 3"
		 * Parent should be set to "Page 1"
		 * Open "Page 3 SP"
		 * Parent should be set to "Page 1 SP"
		 */
		wp_delete_post( $page_4, true );
		$sync->sync_with_translations( $page_3 );
		$this->check_page_hierarchy( array( $page_1, $page_1_es ), array( $page_3, $page_3_es ) );

		$settings['sync_page_parent'] = false;
		$sync = new WPML_Post_Synchronization( $settings, $wpml_post_translations, $sitepress );

		$args = array(
			array(
				'parent' => '0',
				'ID'     => array( $page_1, $page_3, $page_5 ),
				'check'  => array(
					'expected' => array( '0', '0', '0', $page_1_es ),
					'post_ids' => array( $page_1, $page_3, $page_5, $page_3_es ),
				),
			),
			array(
				'parent' => $page_5_es,
				'ID'     => array( $page_2_es, $page_3_es, $page_4_es ),
				'check'  => array(
					'expected' => array( $page_5_es, $page_5_es, $page_5_es, '0', '0', '0' ),
					'post_ids' => array( $page_2_es, $page_3_es, $page_4_es, $page_1, $page_3, $page_5 ),
				),
			),
			array(
				'parent' => $page_1,
				'ID'     => $page_3,
				'check'  => array(
					'expected' => array( $page_1, $page_5_es ),
					'post_ids' => array( $page_3, $page_3_es ),
				),
			),
			array(
				'parent' => '0',
				'ID'     => $page_2_es,
				'check'  => array(
					'expected' => array( '0', $page_5_es, $page_1, '0' ),
					'post_ids' => array( $page_2_es, $page_3_es, $page_3, $page_1 ),
				),
			),
		);
		$this->update_parent_and_check_hierarchy( $args, $sync );
	}

	protected function update_parent_and_check_hierarchy( $testcases, $sync ) {
		foreach ( $testcases as $tc ) {
			$post_ids = is_array( $tc['ID'] ) ? $tc['ID'] : array( $tc['ID'] );
			foreach ( $post_ids as $post_id ) {
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_parent' => $tc['parent'],
					)
				);
				$sync->sync_with_translations( $post_id );
			}
			$this->check_page_hierarchy( $tc['check']['expected'], $tc['check']['post_ids'] );
		}
	}

	protected function check_page_hierarchy( $expected, $post_ids ) {
		wp_cache_init();
		foreach ( $expected as $key => $expected_parent ) {
			$this->assertEquals( $expected_parent, wp_get_post_parent_id( $post_ids[ $key ] ) );
		}
	}
}