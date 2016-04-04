<?php
require_once ICL_PLUGIN_PATH . '/inc/url-handling/wpml-lang-url-validator.class.php';

class Test_WPML_Lang_URL_Validator extends WPML_UnitTestCase {

	public function test_print_explanation() {
		$sitepress_mock      = $this->get_sitepress_mock();
		$def_lang            = 'de';
		$def_display_name    = 'German';
		$sample_display_name = 'English';
		$sample_lang         = 'en';
		$abs_home            = 'http://www.example.com';
		$sitepress_mock->method( 'get_default_language' )->willReturn( $def_lang );
		$sitepress_mock->method( 'get_language_details' )->willReturnMap( array(
			                                                                  array(
				                                                                  $def_lang,
				                                                                  array( 'display_name' => $def_display_name )
			                                                                  ),
			                                                                  array(
				                                                                  $sample_lang,
				                                                                  array( 'display_name' => $sample_display_name )
			                                                                  )
		                                                                  ) );
		$url_converter_mock = $this->get_url_converter_mock();
		$url_converter_mock->method( 'get_abs_home' )->willReturn( $abs_home );
		/** @var WP_Http $http_mock */
		$http_mock = $this->getMockBuilder( 'WP_Http' )->disableOriginalConstructor()->getMock();
		$subject   = new WPML_Lang_URL_Validator( $http_mock, $url_converter_mock, '', $sitepress_mock );
		$html      = $subject->print_explanation( $sample_lang );
		$dom       = new DOMDocument();
		$dom->loadHTML( $html );
		$output     = $dom->getElementsByTagName( 'span' )->item( 0 )->textContent;
		$lang_parts = array_map( 'trim', explode( ',', $output ) );
		$parts      = array();
		foreach ( $lang_parts as $lang ) {
			$lang_elements = array_map( 'trim', explode( '-', $lang ) );
			$parts         = array_merge( $parts, $lang_elements );
		}
		foreach (
			array(
				'(' . $abs_home . '/',
				$def_display_name,
				$abs_home . '/' . $sample_lang . '/',
				$sample_display_name . ')'
			) as $element
		) {
			$this->assertContains( $element, $parts );
		}
	}
}