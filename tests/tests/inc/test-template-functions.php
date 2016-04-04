<?php

class Test_Template_Functions extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2573
	 */
	function test_wpml_get_active_languages_filter_before_wp_hook() {
		global $wp_actions, $sitepress;
		$bk_wp_actions = $wp_actions;
		$active_langs  = $sitepress->get_active_languages();
		$lang_negotiation = $sitepress->get_setting( 'language_negotiation_type' );
		if ( ! in_array( $lang_negotiation, array( 1, 2, 3 ) ) ){
			$sitepress->set_setting( 'language_negotiation_type', 3 );
		}

		$this->switch_to_front();
		unset( $wp_actions['wp'] );
		$langs = wpml_get_active_languages_filter( '' );
		$this->assertFalse( empty( $langs ) );

		$wp_actions['wp'] = 1;
		$langs = wpml_get_active_languages_filter( '' );
		$this->assertFalse( empty( $langs ) );

		// Restore globals and settings
		$wp_actions = $bk_wp_actions;
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2573
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/layouts-493
	 */
	public function test_wpml_get_active_languages_filter_in_backend() {
		global $sitepress, $wp_actions;
		$bk_wp_actions = $wp_actions;
		$def_lang      = $sitepress->get_default_language();
		$active_langs  = $sitepress->get_active_languages();
		$query_str     = 'skip_missing=0';
		$post_id_orig  = wpml_test_insert_post( $def_lang );

		$pages = array(
			'admin.php?page=some_page',
			'post.php?post=' . $post_id_orig . '&action=edit&lang=' . $def_lang,
		);

		foreach ( $pages as $page ) {
			$this->switch_to_admin( $page );

			// Make sure "wp" hook has not been fired
			unset( $wp_actions['wp'] );

			$ls_languages = wpml_get_active_languages_filter( null, $query_str );

			$this->assertInternalType( 'array', $ls_languages, '$languages should be an array' );
			$this->assertEquals( count( $active_langs ), count( $ls_languages ), 'different amount of elements in $active_langs and $ls_languages' );

			foreach ( $ls_languages as $code => $language ) {
				$this->assertArrayHasKey( 'url', $language, 'missing url key' );
				$this->assertArrayHasKey( 'country_flag_url', $language, 'missing country_flag_url key' );
			}
		}

		// restore
		$wp_actions = $bk_wp_actions;

		$this->switch_to_front();
	}
}