<?php
class Test_TranslationManagement extends WPML_UnitTestCase {

	/** @var int $mock_job_id */
	private $mock_job_id = 20;
	private $mock_rid = 10;

	function test_save_job_fields_from_post() {
		global $iclTranslationManagement;

		$this->assertEquals( 0, did_action( 'wpml_save_job_fields_from_post' ) );
		$iclTranslationManagement->save_job_fields_from_post( 4 );
		$this->assertEquals( 1, did_action( 'wpml_save_job_fields_from_post' ) );
	}

	function test_process_request() {
		global $iclTranslationManagement;

		$this->assertEquals( 0, did_action( 'wpml_save_translation_data' ) );
		$data = array( 'icl_tm_action' => 'save_translation' );
		$iclTranslationManagement->process_request( $data );
		$this->assertEquals( 1, did_action( 'wpml_save_translation_data' ) );
	}

	function test_save_translation() {
		global $iclTranslationManagement;

		$this->assertEquals( 0, did_action( 'wpml_save_translation_data' ) );
		$iclTranslationManagement->save_translation( array() );
		$this->assertEquals( 1, did_action( 'wpml_save_translation_data' ) );
	}

	function test_wpml_add_translation_job() {
		global $iclTranslationManagement;

		$this->assertEquals( 0, did_action( 'wpml_add_translation_job' ) );
		add_filter( 'wpml_rid_to_untranslated_job_id', array( $this, 'mock_added_job_id_filter' ), 10, 2 );
		$res = $iclTranslationManagement->add_translation_job( $this->mock_rid, false, false );
		$this->assertEquals( 1, did_action( 'wpml_add_translation_job' ) );
		$this->assertEquals( $this->mock_job_id, $res );
		remove_filter( 'wpml_rid_to_untranslated_job_id', array( $this, 'mock_added_job_id_filter' ) );
	}

	function mock_added_job_id_filter( $default, $rid ) {
		$this->assertFalse( $default );
		$this->assertEquals( $this->mock_rid, $rid );

		return $this->mock_job_id;
	}

	public function test_add_translator() {
		global $wpdb;

		$user_factory = new WP_UnitTest_Factory_For_User();
		$user = $user_factory->create_and_get();
		$tm = new TranslationManagement();

		$language_pairs = array( 'en' => array( 'ru' => 1 ) );

		$tm->add_translator( $user->ID, $language_pairs );
		$this->assertEquals( $language_pairs, get_user_meta( $user->ID, $wpdb->prefix . 'language_pairs', true ) );
	}

	public function test_edit_translator() {
		global $wpdb;
		$tm = new TranslationManagement();

		$user_id = rand();

		foreach ( array(
					  array( 'en' => array( 'ru' => 1 ) ),
					  array( 'en' => array( 'ru' => 1, 'de' => 1 ) ),
					  array(),
				  ) as $language_pair
		) {

			$tm->edit_translator( $user_id, $language_pair );
			if ( empty( $language_pair ) ) {
				$this->assertEquals( '', get_user_meta( $user_id, $wpdb->prefix . 'language_pairs', true ) );
			} else {
				$this->assertEquals( $language_pair, get_user_meta( $user_id, $wpdb->prefix . 'language_pairs', true ) );
			}
		}
	}

	public function test_remove_translator() {
		global $wpdb;
		$tm = new TranslationManagement();

		$user_id = rand();
		$language_pairs = array( 'en' => array( 'ru' => 1 ) );
		$tm->add_translator( $user_id, $language_pairs );

		$this->assertEquals( $language_pairs, get_user_meta( $user_id, $wpdb->prefix . 'language_pairs', true ) );

		$tm->remove_translator( $user_id );
		$this->assertEquals( '', get_user_meta( $user_id, $wpdb->prefix . 'language_pairs', true ) );
	}

	public function test_icl_tm_add_translator() {
		global $wpdb;

		$tm = new TranslationManagement();
		$user_factory = new WP_UnitTest_Factory_For_User();
		$user = $user_factory->create_and_get();

		$language_pairs = array( 'en' => array( 'ru' => 1 ) );
		$data['user_id'] = $user->ID;
		$data['from_lang'] = 'en';
		$data['to_lang'] = 'ru';

		$tm->icl_tm_add_translator( $data );
		$this->assertEquals( $language_pairs, get_user_meta( $user->ID, $wpdb->prefix . 'language_pairs', true ) );

		// Check message.
		$message = array(
			'id'   => 'icl_tm_message_add_translator',
			'type' => 'updated',
			'text' => sprintf( __( '%s has been added as a translator for this site.', 'sitepress' ), $user->data->display_name )
		);

		$this->assertArraySubset( $message, ICL_AdminNotifier::get_message( 'icl_tm_message_add_translator' ) );
	}

	public function test_icl_tm_remove_translator() {
		global $wpdb;
		$tm = new TranslationManagement();
		$user_factory = new WP_UnitTest_Factory_For_User();
		$user = $user_factory->create_and_get();

		$data['user_id'] = $user->ID;
		$tm->icl_tm_remove_translator( $data );

		// Check message.
		$message = array(
			'id'   => 'icl_tm_message_remove_translator',
			'type' => 'updated',
			'text' => sprintf( __( '%s has been removed as a translator for this site.', 'sitepress' ), $user->data->display_name )
		);

		$this->assertArraySubset( $message, ICL_AdminNotifier::get_message( 'icl_tm_message_remove_translator' ) );
	}

	public function test_icl_tm_cancel_jobs() {
		$tm = new TranslationManagement();
		$tm_mock = $this->get_core_tm_mock();
		$tm_mock->method( 'cancel_translation_request' )->willReturn( true );
		$message = array(
			'id'   => 'icl_tm_message_cancel_jobs',
			'type' => 'updated',
		);

		foreach ( array( null, rand() ) as $icl_translation_id ) {
			if ( null === $icl_translation_id ) {
				$data = array();
				$message['text'] = __( 'No Translation requests selected.', 'sitepress' );
			} else {
				$data['icl_translation_id'] = $icl_translation_id;
				$message['text'] = __( 'Translation requests cancelled.', 'sitepress' );
			}

			$tm->icl_tm_cancel_jobs( $data );
			$this->assertArraySubset( $message, ICL_AdminNotifier::get_message( 'icl_tm_message_cancel_jobs' ) );
		}
	}

	public function test_icl_tm_save_notification_settings() {
		$tm                   = new TranslationManagement();
		$data['notification'] = 'dummy';
		$tm->icl_tm_save_notification_settings( $data );
		$message = array(
			'id'   => 'icl_tm_message_save_notification_settings',
			'type' => 'updated',
			'text' => __( 'Preferences saved.', 'sitepress' ),
		);

		$this->assertArraySubset( $message, ICL_AdminNotifier::get_message( 'icl_tm_message_save_notification_settings' ) );
	}

	public function test_get_blog_not_translators() {
		/* @var TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement, $wpdb;
		$not_translators = $iclTranslationManagement->get_blog_not_translators();
		$this->assertEquals( 'admin', $not_translators[0]->user_login );
		$user_caps = get_user_meta( $not_translators[0]->ID, "{$wpdb->prefix}capabilities" );
		$user_caps[0]['translate'] = true;
		update_user_meta( $not_translators[0]->ID, "{$wpdb->prefix}capabilities", $user_caps );
		$not_translators = $iclTranslationManagement->get_blog_not_translators();
		$this->assertEquals( 0, count( $not_translators ) );
	}
}