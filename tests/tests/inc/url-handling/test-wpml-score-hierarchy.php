<?php

class Test_WPML_Score_Hierarchy extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2144
	 */
	public function test_get_possible_ids_ordered() {
		$parent_slugs = array( rand_str( 10 ), rand_str( 10 ) );
		$child_slugs  = array( rand_str( 10 ), rand_str( 10 ), rand_str( 10 ) );
		$parents      = array(
			$parent_slugs[0] => (object) array(
				'ID'          => '7',
				'post_name'   => $parent_slugs[0],
				'post_parent' => '0',
				'parent_name' => null
			),
			$parent_slugs[1] => (object) array(
				'ID'          => '5',
				'post_name'   => $parent_slugs[1],
				'post_parent' => '0',
				'parent_name' => null,
			)
		);
		$mock_dataset = array(
			(object) array(
				'ID'          => '9',
				'post_name'   => $child_slugs[0],
				'post_parent' => '5',
				'parent_name' => $parent_slugs[1],
			),
			(object) array(
				'ID'          => '11',
				'post_name'   => $child_slugs[0],
				'post_parent' => '7',
				'parent_name' => $parent_slugs[0],
			),
			(object) array(
				'ID'          => '23',
				'post_name'   => $child_slugs[2],
				'post_parent' => '13',
				'parent_name' => $child_slugs[1],
			),
			(object) array(
				'ID'          => '22',
				'post_name'   => $child_slugs[2],
				'post_parent' => '12',
				'parent_name' => $child_slugs[1],
			),
			(object) array(
				'ID'          => '13',
				'post_name'   => $child_slugs[1],
				'post_parent' => '11',
				'parent_name' => $child_slugs[0],
			),
			(object) array(
				'ID'          => '12',
				'post_name'   => $child_slugs[1],
				'post_parent' => '9',
				'parent_name' => $child_slugs[0],
			)
		);

		$correct_results = array( $parent_slugs[0] => array( 23, 22 ), $parent_slugs[1] => array( 22, 23 ) );
		foreach ( $parents as $parent_slug => $parent ) {
			$subject = new WPML_Score_Hierarchy( array_merge( $mock_dataset, array( $parent ) ), array_merge( array( $parent_slug ), $child_slugs ) );
			$this->assertEquals( $correct_results[ $parent_slug ], $subject->get_possible_ids_ordered() );
		}
	}
}