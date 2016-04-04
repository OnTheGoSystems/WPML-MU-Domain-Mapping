<?php

class Test_WPML_Taxonomy_Translation extends WPML_UnitTestCase {

	public function test_render() {
		foreach (
			array(
				array( '', false ),
				array( 'category', true )
			) as $data
		) {
			$dom = $this->render( $data[0] );
			$this->assertEquals( 'wrap',
				$dom->getElementsByTagName( 'div' )->item( 0 )->attributes->getNamedItem('class')->value );
			$this->assertEquals( $data[1],
				(bool) $dom->getElementById( 'tax-preselected' ) );
			if ( $data[1] ) {
				$this->assertEquals( false,
					(bool) $dom->getElementById( 'tax-selector-hidden' ) );
				$dom = $this->render( $data[0],
					array( 'taxonomy_selector' => false ) );
				$this->assertEquals( true,
					(bool) $dom->getElementById( 'tax-selector-hidden' ) );
			}
		}
	}

	private function render( $taxonomy, $args = array() ) {
		$subject = new WPML_Taxonomy_Translation( $taxonomy, $args );
		ob_start();
		$subject->render();
		$dom = new DOMDocument();
		$dom->loadHTML( ob_get_clean() );

		return $dom;
	}
}