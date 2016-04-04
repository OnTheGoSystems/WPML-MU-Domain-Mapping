<?php

require_once ICL_PLUGIN_PATH . '/menu/wpml-post-status-display.class.php';

class Test_WPML_Post_Status_Display extends WPML_UnitTestCase {

	public function test_get_status_html() {
		global $sitepress;

		$trid = rand ( 1000, 10000 );
		$lang = 'en';
		$second_lang = 'fr';
		$test_type = 'page';

		$post_id = wpml_test_insert_post ( $lang, $test_type, $trid, rand_str () );
		$status_display = new WPML_Post_Status_Display( $sitepress->get_active_languages () );

		$this->assertTrue (
			strpos ( $status_display->get_status_html ( $post_id, $second_lang ), 'add_translation.png' ) !== false
		);

		$translated_post_id = wpml_test_insert_post ( $second_lang, 'page', $trid, rand_str () );

		$this->assertNotEquals ( $translated_post_id, $post_id );

		$status_helper = wpml_get_post_status_helper ();
		$status_helper->reload ();
		$new_status_html = $status_display->get_status_html ( $post_id, $second_lang );

		$this->assertFalse ( strpos ( $new_status_html, 'add_translation.png' ) );
		$this->assertTrue ( (bool) strpos ( $new_status_html, 'edit_translation.png' ) );
		$this->assertTrue (
			(bool) strpos ( $new_status_html, 'post=' . $translated_post_id ),
			$new_status_html
			. " does not contain a link to the correct post id "
			. $translated_post_id . " !"
		);
		$this->assertTrue ( (bool) strpos ( $new_status_html, 'action=edit' ) );
		$this->assertTrue ( (bool) strpos ( $new_status_html, 'post_type=' . $test_type ) );

		$third_lang = 'de';

		$missing_status_html = $status_display->get_status_html ( $post_id, $third_lang );

		$this->assertTrue ( (bool) strpos ( $missing_status_html, 'add_translation.png' ) );
		$this->assertTrue (
			(bool) strpos ( $missing_status_html, 'trid=' . $trid ),
			$missing_status_html
			. " does not contain a link to the correct trid "
			. $trid . " !"
		);
		$this->assertTrue ( (bool) strpos ( $missing_status_html, '-new.php' ) );
		$this->assertTrue ( (bool) strpos ( $missing_status_html, 'post_type=' . $test_type ) );
		$this->assertTrue ( (bool) strpos ( $missing_status_html, 'source_lang=' . $lang ) );
		$this->assertTrue ( (bool) strpos ( $missing_status_html, 'lang=' . $third_lang ) );

		$status_helper->set_update_status ( $translated_post_id, true );

		$update_status_html = $status_display->get_status_html ( $post_id, $second_lang );

		$this->assertTrue ( (bool) strpos ( $update_status_html, 'needs-update.png' ) );
		$this->assertTrue (
			(bool) strpos ( $update_status_html, 'post=' . $translated_post_id ),
			$update_status_html
			. " does not contain a link to the correct post id "
			. $translated_post_id . " !"
		);
		$this->assertTrue ( (bool) strpos ( $update_status_html, 'action=edit' ) );
		$this->assertTrue ( (bool) strpos ( $update_status_html, 'post_type=' . $test_type ) );
	}
}
