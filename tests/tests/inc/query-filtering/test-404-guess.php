<?php

class Test_WPML_404_Guess extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1976
	 */
	public function test_guess_cpt_by_name() {
		$this->check_post_type_set();
		$this->check_post_type_not_set();
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2448
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlmedia-77
	 */
	public function test_with_corrupted_settings() {
		global $wpdb, $wpml_query_filter;

		$wp_api_mock = $this->get_wp_api_mock();
		$post_types  = array(
			'post'       => 'post',
			'page'       => 'page',
			'attachment' => 'attachment'
		);
		$wp_api_mock->method( 'get_post_types' )->willReturn( $post_types );
		$sitepress_mock = $this->get_sitepress_mock( $wp_api_mock );
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array(
			'en' => 1,
			'fr' => 1
		) );
		$sitepress_mock->method( 'get_current_language' )->willReturn( 'en' );
		$sitepress_mock->method( 'get_setting' )->willReturnMap( array(
			array(
				'languages_order',
				false,
				array( 'en' )
			)
		) );
		$subject = new WPML_404_Guess( $wpdb, $sitepress_mock, $wpml_query_filter );
		foreach ( $post_types as $type ) {
			$post_id = wpml_test_insert_post( 'en', $type );
			$post_name = get_post( $post_id )->post_name;
			$wp_query  = new WP_Query();
			$wp_query->set( 'pagename', $post_name );
			list( $name, $type_guessed ) = $subject->guess_cpt_by_name( $post_name, $wp_query );
			$this->assertEquals( $post_name, $name );
			$this->assertEquals( $type, $type_guessed );
		}
	}

	private function check_post_type_set() {
		global $wpml_query_filter;

		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array( 'de' => 1, 'en' => 1, 'fr' => 1 ) );
		$wpdb_mock      = $this->get_wpdb_mock();
		$query_mock     = $this->get_wp_query_mock();
		$type           = 'post';
		$query_mock->method( 'get' )->will( $this->returnValueMap( array( array( 'post_type', '', $type ) ) ) );
		$subject = new WPML_404_Guess( $wpdb_mock, $sitepress_mock, $wpml_query_filter );
		$name    = rand_str( 10 );
		list( $name_guessed, $type_guessed, $changed ) = $subject->guess_cpt_by_name( $name, $query_mock );
		$this->assertFalse( $changed );
		$this->assertEquals( $name, $name_guessed );
		$this->assertEquals( $type, $type_guessed );
	}

	private function check_post_type_not_set() {
		global $wpdb;

		$query_mock  = $this->get_wp_query_mock();
		$wp_api_mock = $this->get_wp_api_mock();
		$wp_api_mock->method( 'get_post_types' )->willReturn( array( 'post', 'page', 'car' ) );
		$sitepress_mock = $this->get_sitepress_mock( $wp_api_mock );
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array( 'de' => 1, 'en' => 1, 'fr' => 1 ) );
		$sitepress_mock->method( 'get_current_language' )->willReturn( 'en' );
		$sitepress_mock->method( 'get_setting' )->will( $this->returnValueMap( array(
			                                                                       array(
				                                                                       'languages_order',
				                                                                       array(),
				                                                                       array( 'en', 'de', 'fr' )
			                                                                       )
		                                                                       ) ) );
		$query_filter_mock = $this->getMockBuilder( 'WPML_Query_Filter' )->disableOriginalConstructor()->getMock();
		$query_filter_mock->method( 'in_translated_types_snippet' )->willReturn( "p.post_type  IN ('post','page','car' ) " );
		/** @var WPML_Query_Filter $query_filter_mock */
		$subject = new WPML_404_Guess( $wpdb, $sitepress_mock, $query_filter_mock );
		$this->check_type_not_set_without_date( $query_mock, $subject );
		$this->check_type_not_set_with_date( $query_mock, $subject );
	}

	/**
	 * @param WP_Query       $query_mock
	 * @param WPML_404_Guess $subject
	 */
	private function check_type_not_set_without_date( $query_mock, $subject ) {
		$name = 'auto';
		$type = 'car';
		wpml_test_insert_post( 'en', $type, false, $name );
		list( $name_guessed, $type_guessed, $changed ) = $subject->guess_cpt_by_name( $name, $query_mock );
		$this->assertTrue( $changed );
		$this->assertEquals( $name, $name_guessed );
		$this->assertEquals( $type, $type_guessed );
		$non_existent_name = 'non_existent';
		list( $name_guessed, $type_guessed, $changed ) = $subject->guess_cpt_by_name( $non_existent_name, $query_mock );
		$this->assertFalse( $changed );
		$this->assertEquals( $non_existent_name, $name_guessed );
		$this->assertFalse( (bool) $type_guessed );
	}

	/**
	 * @param WP_Query|PHPUnit_Framework_MockObject_MockObject $query_mock
	 * @param WPML_404_Guess                                   $subject
	 */
	private function check_type_not_set_with_date( $query_mock, $subject ) {
		$query_mock->method( 'get' )->will( $this->returnValueMap( array( array( 'year', '', gmstrftime( '%Y' ) ) ) ) );
		$this->check_type_not_set_without_date( $query_mock, $subject );
	}
}