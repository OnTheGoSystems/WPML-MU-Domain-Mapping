<?php

class Test_WPML_Name_Query_Filter_Untranslated extends WPML_UnitTestCase {
	function test_filter_page_name() {
		$this->check_par_child_same_slug();
		$this->check_three_nested_levels();
	}


	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1844
	 */
	private function check_three_nested_levels() {
		$this->check_three_slugs( array( 'test', 'test', 'test' ),
			array( 'de' => '9', 'en' => '9' ),
			'test/test' );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1961
	 */
	private function check_par_child_same_slug() {
		$this->check_three_slugs( array( 'parent', 'child', 'child' ),
			array( 'de' => '12', 'en' => '12' ),
			'parent/child/child' );
	}

	/**
	 * @param string[] $slugs
	 * @param array $id_array
	 * @param string $pagename
	 */
	private function check_three_slugs( $slugs, $id_array, $pagename ) {
		$wpdb_mock = $this->get_wpdb_mock();
		$wpdb_mock->method( 'get_results' )->willReturn( array(
			(object) array(
				'ID'          => '9',
				'post_name'   => $slugs[1],
				'post_parent' => '5',
				'parent_name' => $slugs[0],
			),
			(object) array(
				'ID'          => '12',
				'post_name'   => $slugs[2],
				'post_parent' => '9',
				'parent_name' => $slugs[1],
			),
			(object) array(
				'ID'          => '5',
				'post_name'   => $slugs[0],
				'post_parent' => '0',
				'parent_name' => null,
			),
		) );
		foreach ( $id_array as $current_lang => $correct_child_id ) {
			$sitepress_mock        = $this->get_sitepress_mock();
			$post_translation_mock = $this->get_post_translation_mock();
			$sitepress_mock->method( 'get_active_languages' )->willReturn( array( 'en' => 1, 'de' => 1 ) );
			$sitepress_mock->method( 'get_current_language' )->willReturn( $current_lang );
			$sitepress_mock->method( 'is_translated_post_type' )->willReturn( false );
			$post_translation_mock->method( 'get_element_lang_code' )->willReturn( null );
			$subject                = new WPML_Name_Query_Filter_Untranslated( 'downloads', $sitepress_mock, $post_translation_mock, $wpdb_mock );
			$test_query             = new WP_Query();
			$test_query->query      = array(
				'name'      => '',
				'downloads' => $pagename,
			);
			$test_query->query_vars = $test_query->query;
			list( $filter_result )  = $subject->filter_page_name( $test_query );
			$this->assertEquals( (string) $correct_child_id, (string) $filter_result->query_vars['p'] );
		}
	}
}