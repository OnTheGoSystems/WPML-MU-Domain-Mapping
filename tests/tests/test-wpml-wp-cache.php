<?php

class Test_WPML_WP_Cache extends WPML_UnitTestCase {

	function setUp() {
		global $sitepress;
		
		$sitepress->switch_lang('en');
		icl_set_setting( 'default_language', 'en', true );
		$this->switch_to_langs_as_params();
		remove_all_filters('icl_current_language');
		parent::setUp();
	}

	function test_cache() {
		
		$cache     = new WPML_WP_Cache( 'my-group' );
		$cache_key = 'test-key';
		
		// test not found
		$found = true;
		$cache->get( $cache_key, $found );
		$this->assertFalse( $found );
		
		$data = array( 'string'       => 'string',
					   'string-empty' => '',
					   'int'          => 0,
					   'int-2'        => 10,
					   'bool-false'   => false,
					   'bool-true'    => true,
					   'null'         => null,
					   'array-empty'  => array(),
					   'array'        => array( 'key1' => 'data',
											    'key2' => 10 )
					   );
		
		foreach( $data as $key => $test_data ) {
			$cache->set( $cache_key, $test_data );
			$found = false;
			$value = $cache->get( $cache_key, $found );
			$this->assertTrue( $found );
			$this->assertEquals( $test_data, $value );
		}
	}
	
	function test_get_ls_languages() {
		global $sitepress, $wpml_post_translations, $wp_actions;
		$bk_wp_actions = $wp_actions;
		
		$org_post_id = wpml_test_insert_post( 'en' );
		$this->assertEquals( 'en', $wpml_post_translations->get_element_lang_code( $org_post_id ) );
		$trans_post_id = wpml_test_insert_post( 'fr',
		                                        get_post_type( $org_post_id ),
		                                        $wpml_post_translations->get_element_trid( $org_post_id )
											  );
		$this->assertEquals( 'fr', $wpml_post_translations->get_element_lang_code( $trans_post_id ) );

		$org_post_id_2 = wpml_test_insert_post( 'en' );
		$this->assertEquals( 'en', $wpml_post_translations->get_element_lang_code( $org_post_id_2 ) );
		$trans_post_id_2 = wpml_test_insert_post( 'fr',
		                                        get_post_type( $org_post_id_2 ),
		                                        $wpml_post_translations->get_element_trid( $org_post_id_2 )
											  );

		$this->clear_get_ls_languages_cache();
		$url = get_permalink( $org_post_id );
		$this->switch_to_front( $url );
		unset( $wp_actions['wp'] );
		$ls_1 = $sitepress->get_ls_languages();
		$this->assertEquals( 'http://example.org/?p=' . $org_post_id, $ls_1[ 'en' ][ 'url' ] );
		$this->assertEquals( 'http://example.org/?p=' . $trans_post_id . '&lang=fr', $ls_1[ 'fr' ][ 'url' ] );
		
		// get it again. This time it will come from the cache.
		$ls_1_from_cache = $sitepress->get_ls_languages();
		$this->assertEquals( 'http://example.org/?p=' . $org_post_id, $ls_1_from_cache[ 'en' ][ 'url' ] );
		$this->assertEquals( 'http://example.org/?p=' . $trans_post_id . '&lang=fr', $ls_1_from_cache[ 'fr' ][ 'url' ] );
		
		// Create a new query using the second post
		$this->clear_get_ls_languages_cache();
		$url = get_permalink( $org_post_id_2 );
		$this->switch_to_front( $url );
		unset( $wp_actions['wp'] );
		$ls_2 = $sitepress->get_ls_languages();
		$this->assertEquals( 'http://example.org/?p=' . $org_post_id_2, $ls_2[ 'en' ][ 'url' ] );
		$this->assertEquals( 'http://example.org/?p=' . $trans_post_id_2 . '&lang=fr', $ls_2[ 'fr' ][ 'url' ] );

		// get it again. This time it will come from the cache.
		$ls_2_from_cache = $sitepress->get_ls_languages();
		$this->assertEquals( 'http://example.org/?p=' . $org_post_id_2, $ls_2_from_cache[ 'en' ][ 'url' ] );
		$this->assertEquals( 'http://example.org/?p=' . $trans_post_id_2 . '&lang=fr', $ls_2_from_cache[ 'fr' ][ 'url' ] );

		// Restore
		$wp_actions = $bk_wp_actions;
	}

	/**
	 * @issue https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2466
	 */
	function test_get_ls_languages_with_no_wp_query() {
		global $sitepress, $wp_query;

		$this->clear_get_ls_languages_cache();

		$wp_query = null;

		$active_languages = $sitepress->get_ls_languages();

		$this->assertCount( 6, $active_languages);
	}

	private function clear_get_ls_languages_cache ( ) {
		global $sitepress, $wp_query;
		
		$current_language = $sitepress->get_current_language();
		$default_language = $sitepress->get_default_language();

		$cache_key_args   = array( 'default' );
		$cache_key_args[] = $current_language;
		$cache_key_args[] = $default_language;
		$cache_key_args[] = $wp_query->request;
		$cache_key_args   = array_filter( $cache_key_args );
		$cache_key        = md5( wp_json_encode( $cache_key_args ) );
		$cache_group      = 'ls_languages';
		wp_cache_set( $cache_key, null, $cache_group );
	}
}

