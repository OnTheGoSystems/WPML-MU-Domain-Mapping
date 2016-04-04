<?php

class Test_WPML_Translation_Tree extends WPML_UnitTestCase {

	/**
	 * Checks that the constructor throws an exception,
	 * when no terms are given to be sorted
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function test_constructor() {
		global $sitepress;

		new WPML_Translation_Tree( $sitepress, 'category', array() );
	}

	public function test_get_alphabetically_ordered_list() {
		global $sitepress;

		$subject = new WPML_Translation_Tree( $sitepress, 'category', array(
			(object) array(
				'term_taxonomy_id' => 1,
				'term_id'          => 1,
				'trid'             => 1,
				'parent'           => 0,
				'language_code'    => 'en',
				'name'             => 'a'
			),
			(object) array(
				'term_taxonomy_id' => 2,
				'term_id'          => 2,
				'trid'             => 2,
				'parent'           => 1,
				'language_code'    => 'en',
				'name'             => 'ac'
			),
			(object) array(
				'term_taxonomy_id' => 3,
				'term_id'          => 3,
				'trid'             => 2,
				'parent'           => 4,
				'language_code'    => 'de',
				'name'             => 'b'
			),
			(object) array(
				'term_taxonomy_id' => 4,
				'term_id'          => 4,
				'trid'             => 1,
				'parent'           => 0,
				'language_code'    => 'de',
				'name'             => 'bc'
			),
		) );
		$trids   = $subject->get_alphabetically_ordered_list();
		$this->assertCount( 2, $trids );
	}
}