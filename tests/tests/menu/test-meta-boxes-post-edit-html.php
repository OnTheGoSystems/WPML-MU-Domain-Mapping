<?php

require_once ICL_PLUGIN_PATH . '/menu/wpml-post-status-display.class.php';

class Test_WPML_Meta_Boxes_Post_Edit_HTML extends WPML_UnitTestCase {

	/** @var  WPML_Meta_Boxes_Post_Edit_HTML $subject */
	private $subject;

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1901
	 */
	function test_render_languages() {
		/** @var WPML_Admin_Post_Actions|PHPUnit_Framework_MockObject_MockObject $post_translations_mock */
		list( $sitepress_mock, $post_translations_mock ) = $this->get_basic_mocks();
		$this->subject = new WPML_Meta_Boxes_Post_Edit_HTML( $sitepress_mock, $post_translations_mock );
		$this->assertEquals( '', $this->get_output() );
		$post_id       = rand( 10, 100 );
		$original_post = (object) array( 'post_type' => 'post', 'ID' => $post_id, 'post_status' => 'publish' );
		$output        = $this->get_output( $original_post );
		$dom           = new DOMDocument();
		$dom->loadHTML( $output );

		$this->assertNull( $dom->getElementById( 'icl_translate_independent' ) );
		$icl_post_lang_select = $dom->getElementById( 'icl_post_language' );
		$this->assertNull( $dom->getElementById( 'icl_cfo' ) );
		$this->assertNotNull( $icl_post_lang_select );
		$options = $icl_post_lang_select->childNodes;
		$this->assertEquals( 3, $options->length );
		$this->check_copy_and_duplicate_btn();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1947
	 */
	private function check_copy_and_duplicate_btn() {
		$post_id         = rand( 10, 100 );
		$translated_post = (object) array(
			'post_type'    => 'post',
			'ID'           => $post_id,
			'post_status'  => 'auto_draft',
			'post_content' => ''
		);
		/** @var WPML_Admin_Post_Actions|PHPUnit_Framework_MockObject_MockObject $post_translations_mock */
		list( $sitepress_mock, $post_translations_mock ) = $this->get_basic_mocks();

		$lang                = 'de';
		$_GET['lang']        = $lang;
		$source_lang         = 'en';
		$_GET['source_lang'] = $source_lang;
		$trid                = rand( 10, 100 );
		$_GET['trid']        = $trid;
		$original_id         = $post_id - 1;

		$post_translations_mock->method( 'get_element_trid' )->willReturnMap( array( array( $original_id, $trid ) ) );
		$post_translations_mock->method( 'get_save_post_trid' )->willReturn( $trid );
		$post_translations_mock->method( 'get_element_id' )->willReturnMap( array(
			                                                                    array(
				                                                                    $source_lang,
				                                                                    $trid,
				                                                                    $original_id
			                                                                    )
		                                                                    ) );
		$this->subject = new WPML_Meta_Boxes_Post_Edit_HTML( $sitepress_mock, $post_translations_mock );

		$this->assertEquals( '', $this->get_output() );
		$output = $this->get_output( $translated_post );
		$dom    = new DOMDocument();
		$dom->loadHTML( $output );

		$this->assertNull( $dom->getElementById( 'icl_translate_independent' ) );
		$icl_copy_from = $dom->getElementById( 'icl_cfo' );
		$icl_set_dupl  = $dom->getElementById( 'icl_set_duplicate' );
		$this->assertNotNull( $icl_copy_from );
		$this->assertNotNull( $icl_set_dupl );
		$this->assertEquals( (string) $original_id, $icl_set_dupl->getAttribute( 'data-wpml_original_post_id' ) );
	}

	/**
	 * @return array
	 */
	private function  get_basic_mocks() {
		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array( 'en' => 1, 'de' => 1, 'fr' => 1 ) );
		$sitepress_mock->method( 'get_current_language' )->willReturn( 'en' );
		/** @var WPML_Admin_Post_Actions|PHPUnit_Framework_MockObject_MockObject $post_translations_mock */
		$post_translations_mock = $this->getMockBuilder( 'WPML_Admin_Post_Actions' )->disableOriginalConstructor()->getMock();
		$post_translations_mock->method( 'get_allowed_target_langs' )->willReturn( array( 'en', 'de', 'fr' ) );
		$post_translations_mock->method( 'get_element_translations' )->willReturn( array() );
		$post_translations_mock->method( 'get_source_lang_code' )->willReturn( array() );

		return array( $sitepress_mock, $post_translations_mock );
	}

	private function get_output( $post = null ) {
		ob_start();
		$this->subject->render_languages( $post );

		return ob_get_clean();
	}
}