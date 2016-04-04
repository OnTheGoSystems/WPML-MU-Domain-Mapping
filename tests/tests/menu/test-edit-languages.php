<?php

/**
 * @group languages
 */
class Test_Edit_Languages extends WPML_UnitTestCase {
	private $active_languages        = array();
	private $all_languages           = array();
	private $assert_info             = array();
	private $key_space               = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	private $max_english_name_length = 128;
	private $max_language_code       = 2;
	private $max_locale_length       = 35;
	private $used_language_codes     = array();

	//Tests Setup: Start
	function setUp() {
		parent::setUp();

		require_once ICL_PLUGIN_PATH . '/menu/edit-languages.php';

		$setup_helper = wpml_get_setup_instance();
		$setup_helper->set_active_languages( array( 'en', 'fr', 'de', 'it' ) );
	}

	function tearDown() {
		$setup_helper = wpml_get_setup_instance();
		$setup_helper->set_active_languages( array( 'en', 'fr', 'de', 'it' ) );

		parent::tearDown();
	}
	//Tests Setup: End

	// Tests: Start
	function test_opening_edit_languages() {
		$this->init_all_languages();

		ob_start();

		$editLanguages = new SitePress_EditLanguages();
		$editLanguages->render();

		$content = ob_get_clean();

		$content_dom = new DOMDocument();
		$content_dom->loadHTML($content);

		$finder = new DomXPath($content_dom);
		$classname="icl_edit_languages_show";
		$nodes = $finder->query("//th[contains(@class, '$classname')]");
		foreach ($nodes as $node) {
			$this->assertEquals('display:none;', $node->getAttribute('style'));
		}
	}

	function test_edit_language_with_duplicate_locale() {
		$this->init_active_languages();
		$this->assert_info = array();

		$first_language_code  = 'x1';
		$second_language_code = 'y1';

		$this->used_language_codes[] = $first_language_code;
		$this->used_language_codes[] = $second_language_code;

		$this->clean_up_languages();

		$new_language = array(
			'english_name'   => $this->get_random_language_name(),
			'code'           => $first_language_code,
			'default_locale' => $first_language_code,
			'encode_url'     => false,
			'tag'            => $first_language_code,
			'translations'   => array(),
			'flag_upload'    => false,
		);

		$this->update_language_without_errors( $new_language, true );

		$new_language['english_name']   = $this->get_random_language_name();
		$new_language['code']           = $second_language_code;
		$new_language['tag']            = $new_language['code'];
		$new_language['default_locale'] = $new_language['code'];

		$this->update_language_without_errors( $new_language, true );

		$new_language['default_locale'] = $first_language_code;
		$this->update_language_with_duplicated_locale( $new_language, false );

		$this->clean_up_languages();
	}

	function test_new_language_with_duplicate_locale() {
		$new_language = $this->prepare_new_language_for_duplication( 'default_locale' );
		$this->update_language_with_duplicated_locale( $new_language, true );

		$this->clean_up_languages();
	}

	function test_new_language_with_duplicate_tag() {
		$new_language = $this->prepare_new_language_for_duplication( 'tag' );
		$this->update_language_with_duplicated_tag( $new_language, true );

		$this->clean_up_languages();
	}

	function test_new_language_with_duplicate_name() {
		$new_language = $this->prepare_new_language_for_duplication( 'english_name' );
		$this->update_language_with_duplicated_language_name( $new_language, true );

		$this->clean_up_languages();
	}

	function test_delete_builtin_language() {
		$this->delete_language( 'en', true );
		$this->assertEquals( "Error: This is a built in language. You can't delete it.", $this->assert_info['$errors'], $this->parse_assert_info() );
	}

	function test_new_languages() {
		$this->assert_info = array();

		$length_tests = array(
			array(
				'insert_fails'     => false,
				'missing_required' => false,
				'duplicated_code'  => false,
				'settings'         => array(
					'code'           => 'x1',
					'english_name'   => $this->get_random_language_name(),
					'translations'   => true,
					'default_locale' => $this->get_random_locale(),
					'tag'            => $this->get_random_tag(),
				),
			),
			array(
				'insert_fails'     => false,
				'missing_required' => false,
				'duplicated_code'  => false,
				'settings'         => array(
					'code'           => 'x2',
					'english_name'   => $this->get_random_language_name(),
					'translations'   => true,
					'default_locale' => random_string( 1, $this->key_space ),
					'tag'            => random_string( 1, $this->key_space ),
				),
			),
			array(
				'insert_fails'     => true,
				'missing_required' => false,
				'duplicated_code'  => false,
				'settings'         => array(
					'code'           => 'x3',
					'english_name'   => $this->get_too_long_random_language_name(),
					'translations'   => true,
					'default_locale' => random_string( 1, $this->key_space ),
					'tag'            => random_string( 1, $this->key_space ),
				),
			),
			array(
				'insert_fails'     => true,
				'missing_required' => true,
				'duplicated_code'  => false,
				'settings'         => array(
					'code'           => 'y1',
					'english_name'   => $this->get_random_language_name(),
					'translations'   => true,
					'default_locale' => '',
					'tag'            => '',
				),
			),
			array(
				'insert_fails'     => true,
				'missing_required' => true,
				'duplicated_code'  => false,
				'settings'         => array(
					'code'           => 'y2',
					'english_name'   => $this->get_too_long_random_language_name(),
					'translations'   => true,
					'default_locale' => '',
					'tag'            => '',
				),
			),
			array(
				'insert_fails'     => true,
				'missing_required' => false,
				'duplicated_code'  => false,
				'settings'         => array(
					'code'           => 'y3',
					'english_name'   => $this->get_random_language_name(),
					'translations'   => true,
					'default_locale' => $this->get_random_locale(),
					'tag'            => $this->get_too_long_random_tag(),
				),
			),
			array(
				'insert_fails'     => true,
				'missing_required' => false,
				'duplicated_code'  => false,
				'settings'         => array(
					'code'           => 'y4',
					'english_name'   => $this->get_random_language_name(),
					'translations'   => true,
					'default_locale' => $this->get_too_long_random_locale(),
					'tag'            => $this->get_random_tag(),
				),
			),
			array(
				'insert_fails'     => true,
				'missing_required' => false,
				'duplicated_code'  => false,
				'settings'         => array(
					'code'           => 'y5',
					'english_name'   => $this->get_random_language_name(),
					'translations'   => true,
					'default_locale' => $this->get_too_long_random_locale(),
					'tag'            => $this->get_too_long_random_tag(),
				),
			),
			array(
				'insert_fails'     => true,
				'missing_required' => false,
				'duplicated_code'  => true,
				'settings'         => array(
					'code'           => 'x1',
					'english_name'   => $this->get_random_language_name(),
					'translations'   => true,
					'default_locale' => $this->get_random_locale(),
					'tag'            => $this->get_random_tag(),
				),
			),
		);

		foreach ( $length_tests as $length_test_index => $length_test ) {
			$this->used_language_codes[] = $length_test['settings']['code'];
		}

		$this->init_active_languages();
		$this->clean_up_languages();

		foreach ( $length_tests as $length_test_index => $length_test ) {
			$this->assert_info = array();

			$this->assert_info = array(
				'$length_test_index' => $length_test_index,
				'$length_test'       => $length_test,
			);

			$new_language = array(
				'english_name'   => $length_test['settings']['english_name'],
				'code'           => $length_test['settings']['code'],
				'default_locale' => $length_test['settings']['default_locale'],
				'encode_url'     => false,
				'tag'            => $length_test['settings']['tag'],
				'translations'   => array(),
				'flag_upload'    => false,
			);

			if ( true === $length_test['missing_required'] ) {
				$this->update_language_with_missing_required( $new_language, true );
			} elseif ( true === $length_test['duplicated_code'] ) {
				$this->update_language_with_duplicated_code( $new_language, true );
			} elseif ( true === $length_test['insert_fails'] ) {
				$this->update_language_insert_fails( $new_language, true );
			} else {
				$this->update_language_without_errors( $new_language, true );
			}
		}

		$this->clean_up_languages();
	}

	// Tests: End

	/**
	 * @param array $data
	 * @param array $language_data     This is an associative array with the following keys:
	 *                                 - english_name
	 *                                 - code
	 *                                 - default_locale
	 *                                 - encode_url
	 *                                 - tag
	 *                                 - translations -> an associative array with the language code as key and the translated language name as value
	 *                                 - flag_upload -> boolean
	 *
	 * @return mixed
	 */
	private function build_form_data( $data, $language_data ) {
		if ( $language_data['add'] ) {
			foreach ( $this->active_languages as $translation ) {
				$value = $translation['code'] . ' XXXX';

				$language_data['translations'][ $translation['code'] ] = $value;
			}

			$data['icl_edit_languages']['add'] = array(
				'english_name'   => $language_data['english_name'],
				'code'           => $language_data['code'],
				'default_locale' => $language_data['default_locale'],
				'encode_url'     => $language_data['encode_url'],
				'tag'            => $language_data['tag'],
				'flag_upload'    => $language_data['flag_upload'],
			);

			$data['icl_edit_languages']['add']['translations'] = array();

			foreach ( $this->active_languages as $translation ) {
				$value = isset( $language_data['translations'][ $translation['code'] ] ) ? $language_data['translations'][ $translation['code'] ] : '';

				$data['icl_edit_languages']['add']['translations'][ $translation['code'] ] = $value;
			}
		} else {
			foreach ( $data['icl_edit_languages'] as $language_id => $item_data ) {
				if ( $item_data['code'] === $language_data['code'] ) {
					$data_keys = array_keys( $item_data );
					foreach ( $data_keys as $key ) {
						if ( array_key_exists( $key, $language_data ) && 'translations' !== $key ) {
							$data['icl_edit_languages'][ $language_id ][ $key ] = $language_data[ $key ];
						}
					}
				}
			}
		}

		return $data;
	}

	private function build_input_data() {
		$data = array(
			'icl_edit_languages_action'     => 'update',
			'icl_edit_languages_ignore_add' => 'false',
			'_wpnonce'                      => '02afeb7d69',
			'_wp_http_referer'              => '/wp-admin/admin.php?page=sitepress-multilingual-cms/menu/languages.php&trop=1&action=delete-language&id=66&icl_nonce=61045cfcc1',
			'icl_edit_languages'            => array(),
		);

		foreach ( $this->active_languages as $lang ) {
			$language_data = array(
				'english_name'   => $lang['english_name'],
				'code'           => $lang['code'],
				'add'            => false,
				'default_locale' => $lang['default_locale'],
				'encode_url'     => $lang['encode_url'],
				'tag'            => $lang['tag'],
				'flag_upload'    => false,
			);

			$translations = array();

			foreach ( $this->active_languages as $translation ) {
				$translations[ $translation['code'] ] = isset( $lang['translation'][ $translation['id'] ] ) ? $lang['translation'][ $translation['id'] ] : '';
			}
			$language_data['translations'] = $translations;

			$data['icl_edit_languages'][ $lang['id'] ] = $language_data;
		}

		return $data;
	}

	/**
	 * @param bool  $update_history
	 * @param array $language_codes
	 */
	private function clean_up_languages( $update_history = false, $language_codes = null ) {

		if ( ! $language_codes ) {
			$language_codes = $this->used_language_codes;
		}

		$language_codes = array_unique( $language_codes );

		foreach ( $language_codes as $language_code ) {
			if ( array_key_exists( $language_code, $this->all_languages ) ) {
				$this->delete_language( $language_code, $update_history );
				$this->assertEmpty( $this->assert_info['$errors'], $this->parse_assert_info() );
			}
		}
	}

	private function delete_language( $language_code, $update_history = false ) {
		$this->init_all_languages();
		$editLanguages = new SitePress_EditLanguages();

		$language_id = $this->all_languages[ $language_code ]['id'];
		$editLanguages->delete_language( $language_id );

		$this->assert_info['$errors']   = wp_strip_all_tags( $editLanguages->get_errors() );
		$this->assert_info['$messages'] = wp_strip_all_tags( $editLanguages->get_messages() );

		if ( $update_history && ( $key = array_search( $language_code, $this->used_language_codes, true ) ) !== false ) {
			unset( $this->used_language_codes[ $key ] );
		}
	}

	private function get_all_languages( $field = null ) {
		$this->init_all_languages();

		$results = null;

		if ( ! $field ) {
			$results = $this->all_languages;
		} elseif ( 'code' === $field ) {
			$results = array_keys( $this->all_languages );
		} else {
			$results = array_column( $this->all_languages, $field );
		}

		return $results;
	}

	private function get_random_language_code() {
		return $this->get_random_string_for_active_languages( 'code', $this->max_language_code, $this->max_language_code, '0123456789' );
	}

	/**
	 * @return string
	 */
	private function get_random_language_name() {
		return $this->get_random_string_for_active_languages( 'english_name', 1, $this->max_english_name_length, $this->key_space );
	}

	private function get_random_locale() {
		return $this->get_random_string_for_active_languages( 'default_locale', $this->max_locale_length, $this->max_locale_length, $this->key_space );
	}

	private function get_random_string_for_active_languages( $field, $min_length, $max_length, $key_space ) {
		$existing_data = $this->get_all_languages( $field );
		do {
			$result = random_string( mt_rand( $min_length, $max_length ), $key_space );
		} while ( in_array( strtolower( $result ), array_map( 'strtolower', $existing_data ), true ) );

		return $result;
	}

	private function get_random_tag() {
		return $this->get_random_string_for_active_languages( 'tag', $this->max_locale_length, $this->max_locale_length, $this->key_space );
	}

	private function get_too_long_random_language_name() {
		return $this->get_random_string_for_active_languages( 'english_name', $this->max_english_name_length + 1, $this->max_english_name_length + 1, $this->key_space );
	}

	private function get_too_long_random_locale() {
		return $this->get_random_string_for_active_languages( 'default_locale', $this->max_locale_length + 1, $this->max_locale_length + 1, $this->key_space );
	}

	private function get_too_long_random_tag() {
		return $this->get_random_string_for_active_languages( 'tag', $this->max_locale_length + 1, $this->max_locale_length + 1, $this->key_space );
	}

	private function init_active_languages() {
		global $sitepress, $wpdb;

		$this->active_languages = $sitepress->get_active_languages( true );

		foreach ( $this->active_languages as $lang ) {
			foreach ( $this->active_languages as $lang_translation ) {
				$this->active_languages[ $lang['code'] ]['translation'][ $lang_translation['id'] ] = $sitepress->get_display_language_name( $lang['code'], $lang_translation['code'] );
			}
			$flag                                                      = $sitepress->get_flag( $lang['code'] );
			$this->active_languages[ $lang['code'] ]['flag']           = $flag->flag;
			$this->active_languages[ $lang['code'] ]['from_template']  = $flag->from_template;
			$this->active_languages[ $lang['code'] ]['default_locale'] = $wpdb->get_var( "SELECT default_locale FROM {$wpdb->prefix}icl_languages WHERE code='" . $lang['code'] . "'" );
			$this->active_languages[ $lang['code'] ]['encode_url']     = $lang['encode_url'];
			$this->active_languages[ $lang['code'] ]['tag']            = $lang['tag'];
		}
	}

	private function init_all_languages() {
		global $sitepress;

		$this->all_languages = $sitepress->get_languages( false, false, true );
	}

	private function parse_assert_info() {
		return json_encode( $this->assert_info, JSON_PRETTY_PRINT );
	}

	/**
	 * @param $duplicated_field
	 *
	 * @return mixed
	 */
	private function prepare_new_language_for_duplication( $duplicated_field ) {
		$this->init_active_languages();

		$new_language = array();
		switch ( $duplicated_field ) {
			case 'english_name':
				$new_language[ $duplicated_field ] = $this->get_random_language_name();
				break;
			case 'default_locale':
				$new_language[ $duplicated_field ] = $this->get_random_locale();
				break;
			case 'tag':
				$new_language[ $duplicated_field ] = $this->get_random_tag();
				break;
		}

		$new_language                = $this->sanitize_language_data( $new_language );
		$this->used_language_codes[] = $new_language['code'];

		$this->clean_up_languages();

		$language_data = $this->update_language_without_errors( $new_language, true );

		$language_data_keys = array_keys( $language_data );

		foreach ( $language_data_keys as $language_data_key ) {
			if ( $duplicated_field !== $language_data_key ) {
				switch ( $language_data_key ) {
					case 'english_name':
						$language_data[ $language_data_key ] = $this->get_random_language_name();
						break;
					case 'default_locale':
						$language_data[ $language_data_key ] = $this->get_random_locale();
						break;
					case 'tag':
						$language_data[ $language_data_key ] = $this->get_random_tag();
						break;
				}
			}
		}

		$language_data['code']       = $this->get_random_language_code();
		$this->used_language_codes[] = $language_data['code'];

		return $language_data;
	}

	/**
	 * @param array $language_data
	 *
	 * @return array
	 */
	private function sanitize_language_data( $language_data ) {
		$language_data_defaults = array(
			'english_name'   => $this->get_random_language_name(),
			'code'           => $this->get_random_language_code(),
			'default_locale' => $this->get_random_locale(),
			'encode_url'     => mt_rand( 0, 1 ),
			'tag'            => $this->get_random_tag(),
			'translations'   => array(),
			'flag_upload'    => false,
		);

		$language_data = array_merge( $language_data_defaults, $language_data );

		return $language_data;
	}

	private function send_input_data_to_POST( $data ) {
		foreach ( $data as $key => $value ) {
			$_POST[ $key ] = $value;
		}
	}

	private function update_language_insert_fails( $language_data, $is_new ) {
		$this->update_languages( $language_data, $is_new );
		$this->assertEquals( 'Adding language failed.', $this->assert_info['$errors'], $this->parse_assert_info() );
	}

	private function update_language_with_duplicated_code( $language_data, $is_new ) {
		$this->update_language_with_duplicated_data( $language_data, $is_new, 'The Language code already exists.' );
	}

	private function update_language_with_duplicated_data( $language_data, $is_new, $expected ) {
		$this->update_languages( $language_data, $is_new );
		$this->assertEquals( $expected, $this->assert_info['$errors'], $this->parse_assert_info() );
	}

	private function update_language_with_duplicated_language_name( $language_data, $is_new ) {
		$this->update_language_with_duplicated_data( $language_data, $is_new, 'The Language name already exists.' );
	}

	private function update_language_with_duplicated_locale( $language_data, $is_new ) {
		$this->update_language_with_duplicated_data( $language_data, $is_new, 'The default locale already exists.' );
	}

	private function update_language_with_duplicated_tag( $language_data, $is_new ) {
		$this->update_language_with_duplicated_data( $language_data, $is_new, 'The tag already exists.' );
	}

	private function update_language_with_missing_required( $language_data, $is_new ) {
		$this->update_languages( $language_data, $is_new );
		$this->assertEquals( 'Please, enter required data.', $this->assert_info['$errors'], $this->parse_assert_info() );
	}

	private function update_language_without_errors( $language_data, $is_new ) {
		$language_data = $this->update_languages( $language_data, $is_new );

		$this->assertEmpty( $this->assert_info['$errors'], $this->parse_assert_info() );
		$this->assertEmpty( $this->assert_info['$messages'], $this->parse_assert_info() );

		$language_string_keys = array( 'code', 'english_name', 'default_locale', 'tag' );
		$language_bool_keys   = array( 'encode_url' );

		$language_code = $language_data['code'];

		$this->init_active_languages();
		$active_languages = $this->active_languages;
		$this->assertArrayHasKey( $language_code, $active_languages, $this->parse_assert_info() );

		foreach ( $language_string_keys as $current_key ) {
			$this->assert_info['$language_string_keys[]'] = $current_key;
			$this->assertArrayHasKey( $current_key, $active_languages[ $language_code ], $this->parse_assert_info() );
			$this->assertEquals( $language_data[ $current_key ], $active_languages[ $language_code ][ $current_key ], $this->parse_assert_info() );
		}

		foreach ( $language_bool_keys as $current_key ) {
			$this->assert_info['$language_bool_keys[]'] = $current_key;
			$this->assertArrayHasKey( $current_key, $active_languages[ $language_code ], $this->parse_assert_info() );
			$this->assertEquals( (bool) $language_data[ $current_key ], (bool) $active_languages[ $language_code ][ $current_key ], $this->parse_assert_info() );
		}

		$this->assertArrayHasKey( 'translation', $active_languages[ $language_code ], $this->parse_assert_info() );

		return $language_data;
	}

	private function update_languages( $language_data, $is_new ) {
		$language_data = $this->sanitize_language_data( $language_data );

		$_GLOBALS['pagenow'] = 'admin.php';

		$_SERVER['REQUEST_URI']  = '/wp-admin/admin.php?page=sitepress-multilingual-cms%2Fmenu%2Flanguages.php&trop=1';
		$_SERVER['QUERY_STRING'] = 'page=sitepress-multilingual-cms%2Fmenu%2Flanguages.php&trop=1';
		$_GET['page']            = 'sitepress-multilingual-cms/menu/languages.php';
		$_GET['trop']            = '1';

		$data = $this->build_input_data();

		if ( ! array_key_exists( 'translations', $language_data ) ) {
			$language_data['translations'] = array();
		}
		if ( ! array_key_exists( 'flag_upload', $language_data ) ) {
			$language_data['flag_upload'] = false;
		}
		$language_data['add'] = $is_new;

		$data = $this->build_form_data( $data, $language_data );

		$this->assert_info['$language'] = $language_data;

		$this->send_input_data_to_POST( $data );

		$editLanguages = new SitePress_EditLanguages();

		$editLanguages->update();

		$this->assert_info['active_languages'] = $this->active_languages;
		$this->assert_info['$errors']          = wp_strip_all_tags( $editLanguages->get_errors() );
		$this->assert_info['$messages']        = wp_strip_all_tags( $editLanguages->get_messages() );

		$this->assertEmpty( $this->assert_info['$messages'], $this->parse_assert_info() );

		$this->check_opening_edit_languages_after_save( $editLanguages);
		return $language_data;
	}

	/**
	 * @param SitePress_EditLanguages $editLanguages
	 */
	private function check_opening_edit_languages_after_save($editLanguages) {
		ob_start();

		$editLanguages->render();

		$content = ob_get_clean();

		$content_dom = new DOMDocument();
		$content_dom->loadHTML($content);

		$finder = new DomXPath($content_dom);
		$classname="icl_edit_languages_show";
		$nodes = $finder->query("//th[contains(@class, '$classname')]");
		/** @var DOMElement $node */
		foreach ($nodes as $node) {
			$this->assertEquals('', $node->getAttribute('style'));
		}
	}

}