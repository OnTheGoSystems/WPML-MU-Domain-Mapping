<?php

class Test_WPML_Lang_Domains_Box extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2517
	 */
	public function test_render() {
		$home_url_base    = 'http://domain.com';
		$default_language = 'en';
		foreach ( array( '', '/sub' ) as $subdir ) {
			$home_url    = $home_url_base . $subdir;
			$wp_api_mock = $this->get_wp_api_mock();
			$wp_api_mock->method( 'get_home_url' )->willReturn( $home_url );
			$sitepress = $this->get_sitepress_mock( $wp_api_mock );
			$sitepress->method( 'get_active_languages' )->willReturn( array(
				'de'              => array( 'display_name' => 'German' ),
				$default_language => array( 'display_name' => 'English' ),
				'fr'              => array( 'display_name' => 'French' ),
				'ru'              => array( 'display_name' => 'Russian' )
			) );
			$sitepress->method( 'get_setting' )->willReturnMap( array(
				array(
					'language_domains',
					array(),
					array(
						'fr' => 'domain.fr',
						'ru' => 'http://domain.ru/sub'
					)
				)
			) );
			$sitepress->method( 'get_default_language' )->willReturn( $default_language );
			$sitepress->method( 'convert_url' )->with( $home_url,
				$default_language )->willReturn( $home_url );
			$this->check_renders( $sitepress, $subdir );
		}
	}

	private function check_renders( $sitepress, $subdir ) {
		$subject = new WPML_Lang_Domains_Box( $sitepress );
		$dom     = new DOMDocument();
		$dom->loadHTML( $subject->render() );
		$table = $dom->getElementsByTagName( 'table' )->item( 0 );
		$this->assertEquals( 'language_domains',
			$table->attributes->getNamedItem( 'class' )->value );
		foreach (
			array(
				'fr' => 'domain.fr',
				'ru' => 'domain.ru'
			) as $lang => $domain_text
		) {
			$domain = $dom->getElementById( "language_domain_" . $lang );
			$this->assertEquals( 'http://',
				$domain->parentNode->childNodes->item(1)->textContent );
			if ( $subdir ) {
				$subdir_element = $domain->parentNode->childNodes->item(4);
				$this->assertEquals( 'code', $subdir_element->tagName );
				$this->assertEquals( $subdir, $subdir_element->textContent );
				$this->assertEquals( $domain_text,
					$domain->getAttribute( 'value' ) );
			} else {
				$this->assertEquals( 'input',
					$domain->parentNode->childNodes->item(3)->tagName );
			}
		}
	}
}