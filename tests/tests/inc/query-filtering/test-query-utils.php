<?php

class Test_WPML_Query_Utils extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1949
	 */
	public function test_date_query_has_posts() {
		global $wpdb;
		$lang = 'de';
		wpml_test_insert_post( $lang, 'post' );
		$query_utils = new WPML_Query_Utils( $wpdb );

		$this->assertTrue( (bool) $query_utils->archive_query_has_posts( $lang, gmstrftime( '%Y' ), null, null, 'post' ) );
		$this->assertFalse( (bool) $query_utils->archive_query_has_posts( 'en', gmstrftime( '%Y' ), null, null, 'post' ) );
		$this->assertFalse( (bool) $query_utils->archive_query_has_posts( $lang,
		                                                               gmstrftime( '%Y' ) - 1,
		                                                               null,
		                                                               null,
		                                                               'post' ) );
		$this->assertTrue( (bool) $query_utils->archive_query_has_posts( $lang,
		                                                              gmstrftime( '%Y' ),
		                                                              (int) gmstrftime( '%m' ),
		                                                              null,
		                                                              'post' ) );
		$this->assertFalse( (bool) $query_utils->archive_query_has_posts( 'en',
		                                                               gmstrftime( '%Y' ),
		                                                               (int) gmstrftime( '%m' ),
		                                                               null,
		                                                               'post' ) );
		$this->assertFalse( (bool) $query_utils->archive_query_has_posts( $lang,
		                                                               gmstrftime( '%Y' ) - 1,
		                                                               (int) gmstrftime( '%m' ) - 1,
		                                                               null,
		                                                               'post' ) );
		$this->assertTrue( (bool) $query_utils->archive_query_has_posts( $lang,
		                                                              gmstrftime( '%Y' ),
		                                                              (int) gmstrftime( '%m' ),
		                                                              (int) gmstrftime( '%d' ),
		                                                              'post' ) );
		$this->assertFalse( (bool) $query_utils->archive_query_has_posts( 'en',
		                                                               gmstrftime( '%Y' ),
		                                                               (int) gmstrftime( '%m' ),
		                                                               (int) gmstrftime( '%d' ),
		                                                               'post' ) );
		$this->assertFalse( (bool) $query_utils->archive_query_has_posts( $lang,
		                                                               gmstrftime( '%Y' ) - 1,
		                                                               (int) gmstrftime( '%m' ) - 1,
		                                                               (int) gmstrftime( '%d' ) - 1,
		                                                               'post' ) );
	}
}