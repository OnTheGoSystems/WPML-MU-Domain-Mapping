<?php

/**
 * Class Test_WPML_MU_Domain_Mapping_Filters
 *
 * @group mu-domain-mapping-filters
 */
class Test_WPML_MU_Domain_Mapping_Filters extends WPML_MU_Domain_Mapping_UnitTestCase {

	/** @var PHPUnit_Framework_MockObject_MockObject|wpdb $wpdb */
	private $wpdb;

	/** @var PHPUnit_Framework_MockObject_MockObject|SitePress $sitepress */
	private $sitepress;

	/** @var PHPUnit_Framework_MockObject_MockObject|WPML_WP_API $sitepress */
	private $wp_api;

	/** @var WPML_MU_Domain_Mapping_Filters $subject */
	private $subject;

	public function setUp() {
		parent::setUp();

		$this->wpdb      = $this->getMockBuilder( 'wpdb' )
		                        ->setMethods( array( 'get_var', 'prepare' ) )->getMock();
		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->setMethods( array( 'get_wp_api' ) )->getMock();
		$this->wp_api    = $this->getMockBuilder( 'WPML_WP_API' )
		                        ->setMethods( array( 'is_main_site', 'constant' ) )->getMock();

		$this->wpdb->dmtable = 'some_dm_table';
		$this->wpdb->blogid  = 'some_blog_id_table';

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->subject = new WPML_MU_Domain_Mapping_Filters( $this->wpdb, $this->sitepress );
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		\WP_Mock::expectFilterAdded(
			'wpml_url_converter_get_abs_home',
			array( $this->subject, 'url_converter_get_abs_home_filter' )
		);

		$this->subject->init_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_not_convert_url_if_on_main_site() {
		$url = 'https://example.org/';
		$this->wp_api->method( 'is_main_site' )->willReturn( true );
		$this->assertEquals( $url, $this->subject->url_converter_get_abs_home_filter( $url ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_convert_url_if_MU_plugin_inactive() {
		$url = 'https://example.org/';
		$this->wp_api->method( 'constant' )->with( 'DOMAIN_MAPPING' )->willReturn( null );
		$this->assertEquals( $url, $this->subject->url_converter_get_abs_home_filter( $url ) );
	}

	/**
	 * @test
	 */
	public function it_should_convert_url() {
		$url          = 'https://example.org/';
		$domain       = 'anything.com';
		$expected_url = 'https://anything.com/';

		$this->wp_api->method( 'is_main_site' )->willReturn( false );
		$this->wp_api->method( 'constant' )->with( 'DOMAIN_MAPPING' )->willReturn( true );
		$this->wpdb->method( 'get_var' )->willReturn( $domain );

		\WP_Mock::wpFunction( 'get_option', array(
			'args' => array( 'home' ),
		    'return' => $url,
		));

		\WP_Mock::wpFunction( 'wp_parse_url', array(
		    'return' => function( $url ) {
		    	return parse_url( $url );
		    },
		));


		\WP_Mock::wpFunction( 'trailingslashit', array(
		    'return' => function( $url ) {
		    	return rtrim( $url, '/') . '/';
		    },
		));

		$this->assertEquals( $expected_url, $this->subject->url_converter_get_abs_home_filter( $url ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_convert_url_if_no_domain_found() {
		$url    = 'https://example.org/';
		$domain = null;

		$this->wp_api->method( 'is_main_site' )->willReturn( false );
		$this->wp_api->method( 'constant' )->with( 'DOMAIN_MAPPING' )->willReturn( true );
		$this->wpdb->method( 'get_var' )->willReturn( $domain );

		\WP_Mock::wpFunction( 'get_option', array(
			'args' => array( 'home' ),
		    'return' => $url,
		));

		\WP_Mock::wpFunction( 'wp_parse_url', array(
		    'return' => function( $url ) {
		    	return parse_url( $url );
		    },
		));

		$this->assertEquals( $url, $this->subject->url_converter_get_abs_home_filter( $url ) );
	}

	public function tearDown() {
		unset( $this->wpdb, $this->sitepress, $this->subject );
		parent::tearDown();
	}
}
