<?php
require_once WPML_TEST_DIR . '/util/wpml-menu-translation-unittestcase.class.php';

class Test_iclNavMenu extends WPML_Menu_Translation_UnitTestCase {

	/** @var  iclNavMenu $subject */
	private $subject;

	function setUp() {
		parent::setUp();
		global $iclNavMenu, $sitepress, $wpdb, $wpml_post_translations, $wpml_term_translations;

		$this->subject = $iclNavMenu ? $iclNavMenu : new iclNavMenu( $sitepress,
		                                                             $wpdb,
		                                                             $wpml_post_translations,
		                                                             $wpml_term_translations );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-2562
	 */
	public function test_init() {
		global $sitepress;

		$post_id = wp_insert_post( array(
			'post_type' => 'nav_menu_item',
			'title'     => 'foo'
		) );
		$wpdb    = $sitepress->wpdb();
		$wpdb->insert( $wpdb->prefix . 'icl_translations', array(
			'element_id'    => $post_id,
			'language_code' => 'fr',
			'element_type'  => 'post_attachment'
		) );
		$wpml_post_translations = $sitepress->post_translations();
		$wpml_term_translations = $sitepress->term_translations();

		set_current_screen( 'dashboard' );

		$subject = new iclNavMenu( $sitepress,
			$wpdb,
			$wpml_post_translations,
			$wpml_term_translations );
		$this->assertNull( $wpml_post_translations->get_element_lang_code( $post_id ) );
		$subject->init();
		$this->assertEquals( $sitepress->get_default_language(),
			$wpml_post_translations->get_element_lang_code( $post_id ) );
	}

	public function test_get_menus_without_translation() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress, $wpml_term_translations;

		$menu_term = wp_create_term( 'test_menu', 'nav_menu' );
		$menu_ttid = isset( $menu_term['term_taxonomy_id'] ) ? $menu_term['term_taxonomy_id'] : false;

		$this->assertTrue( (bool) $menu_ttid, 'Menu Term could no be created!' );

		$sitepress->set_element_language_details( $menu_ttid, 'tax_nav_menu', false, 'en', null );

		$menu_trid = $wpml_term_translations->get_element_trid( $menu_ttid );
		$this->assertEquals( 'en', $wpml_term_translations->get_element_lang_code( $menu_ttid ) );
		$this->assertTrue( (bool) $menu_trid );

		$elements_wo_trans = $this->subject->get_menus_without_translation( 'de' );
		$this->assertArrayHasKey( $menu_trid,
		                          $elements_wo_trans,
		                          'Missing Menu Translation was not correctly recognized!' );

		$menu_term_translation = wp_create_term( 'test_menude', 'nav_menu' );
		$menu_ttid_trans       = isset( $menu_term_translation['term_taxonomy_id'] )
			? $menu_term_translation['term_taxonomy_id'] : false;

		$this->assertTrue( (bool) $menu_ttid_trans, 'Menu Term Translation could no be created!' );

		$sitepress->set_element_language_details( $menu_ttid_trans, 'tax_nav_menu', $menu_trid, 'de', 'en' );

		$elements_wo_trans_after = $this->subject->get_menus_without_translation( 'de' );
		$this->assertArrayNotHasKey( $menu_trid,
		                             $elements_wo_trans_after,
		                             'Missing Menu Translation was not correctly recognized!' );
	}

	public function test_load_menu() {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $sitepress, $wpml_term_translations, $wpdb;

		$tag_term      = wp_create_term( 'test_menu', 'post_tag' );
		$category_term = wp_create_term( 'test_menu', 'category' );
		$menu_term     = wp_create_term( 'test_menu', 'nav_menu' );

		$def_lang = $sitepress->get_default_language();

		$category_ttid = isset( $category_term['term_taxonomy_id'] ) ? $category_term['term_taxonomy_id'] : false;
		$menu_ttid     = isset( $menu_term['term_taxonomy_id'] ) ? $menu_term['term_taxonomy_id'] : false;
		$tag_ttid      = isset( $tag_term['term_taxonomy_id'] ) ? $tag_term['term_taxonomy_id'] : false;

		$sitepress->set_element_language_details( $menu_ttid, 'tax_nav_menu', false, $def_lang, null );
		$sitepress->set_element_language_details( $tag_ttid, 'tax_post_tag', false, $def_lang, null );
		$sitepress->set_element_language_details( $category_ttid, 'tax_category', false, $def_lang, null );

		$tag_term_id  = $tag_term['term_id'];
		$menu_term_id = $menu_term['term_id'];
		$cat_term_id  = $category_term['term_id'];

		$wpdb->update( $wpdb->term_taxonomy, array( 'term_id' => $menu_term_id ), array( 'term_id' => $tag_term_id ) );
		$wpdb->update( $wpdb->term_taxonomy, array( 'term_id' => $menu_term_id ), array( 'term_id' => $cat_term_id ) );

		$count_on_term_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE term_id = %d", $menu_term_id )
		);

		$this->assertEquals( 3, $count_on_term_id );

		$menu_trid = $wpml_term_translations->get_element_trid( $menu_ttid );
		$wpml_term_translations->reload();
		$tag_language = $wpml_term_translations->get_element_lang_code( $tag_ttid );
		$tag_trid     = $wpml_term_translations->get_element_trid( $tag_ttid );
		$cat_trid     = $wpml_term_translations->get_element_trid( $category_ttid );
		$loaded_menu  = $this->subject->_load_menu( $menu_term_id );

		$this->assertEquals( $loaded_menu['trid'], $menu_trid );
		$this->assertNotEquals( $loaded_menu['trid'], $tag_trid );
		$this->assertNotEquals( $loaded_menu['trid'], $cat_trid );
		$this->assertEquals( $def_lang, $tag_language );
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1790
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1851
	 */
	public function test_menu_page_language() {
		global $sitepress, $wpdb, $wpml_post_translations, $wpml_term_translations;

		$sec_lang = 'fr';
		list( , $menu_orig, $menu_trans ) = $this->create_menu_and_translation( $sec_lang );
		$menu_options               = get_option( 'nav_menu_options' );
		$menu_options['auto_add']   = isset( $menu_options['auto_add'] ) ? $menu_options['auto_add'] : array();
		$menu_options['auto_add'][] = $menu_orig->term_id;
		$menu_options['auto_add'][] = $menu_trans->term_id;
		update_option( 'nav_menu_options', $menu_options );

		new WPML_Nav_Menu_Actions( $sitepress,
		                           $wpdb,
		                           $wpml_post_translations,
		                           $wpml_term_translations );

		foreach ( array( $menu_orig, $menu_trans ) as $menu ) {
			$this->assertEmpty( wp_get_nav_menu_items( $menu->term_id ) );
		}
		$original_post_id = wpml_test_insert_post( $this->def_lang, 'page' );
		$this->assertEmpty( wp_get_nav_menu_items( $menu_trans->term_id ) );
		$this->assertCount( 1, wp_get_nav_menu_items( $menu_orig->term_id ) );

		$sitepress->make_duplicate( $original_post_id, $sec_lang );
		$original_nav_menu_items = wp_get_nav_menu_items( $menu_orig->term_id );
		$this->assertCount( 1, wp_get_nav_menu_items( $menu_trans->term_id ) );
		$this->assertCount( 1, $original_nav_menu_items );
		$original_page_item = reset( $original_nav_menu_items );
		$this->check_trids( $menu_orig, $menu_trans, false );
		$translations = $this->menu_sync->get_menu_item_translations( $original_page_item, $menu_orig->term_id );
		$this->assertCount( 1, array_filter( $translations ) );
		$this->check_trids( $menu_orig, $menu_trans );
		$this->check_repair_broken_assignment($menu_orig, $menu_trans, $original_page_item);
	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1948
	 *
	 * @param object $menu_orig
	 * @param object $menu_trans
	 * @param array  $page_item
	 *
	 */
	private function check_repair_broken_assignment( $menu_orig, $menu_trans, $page_item ) {
		global $wpdb;

		$this->assertCount( 1, wp_get_nav_menu_items( $menu_trans->term_id ) );
		$wpdb->query($wpdb->prepare("	DELETE FROM {$wpdb->term_relationships}
										WHERE term_taxonomy_id =
											(SELECT term_taxonomy_id
											 FROM {$wpdb->term_taxonomy}
											 WHERE term_id = %d
											 LIMIT 1)",
									$menu_trans->term_id));
		$this->assertCount( 0, wp_get_nav_menu_items( $menu_trans->term_id ) );
		$this->menu_sync->get_menu_item_translations( $page_item, $menu_orig->term_id );
		$this->assertCount( 1, wp_get_nav_menu_items( $menu_trans->term_id ) );
	}

	private function check_trids( $menu_orig, $menu_trans, $equals = true ) {
		global $wpml_post_translations;

		$original_nav_menu_items   = wp_get_nav_menu_items( $menu_orig->term_id );
		$translated_nav_menu_items = wp_get_nav_menu_items( $menu_trans->term_id );
		$original_page_item_trid   = $wpml_post_translations->get_element_trid( reset( $original_nav_menu_items )->ID );
		$translated_page_item_trid = $wpml_post_translations->get_element_trid( reset( $translated_nav_menu_items )->ID );

		if ( $equals === true ) {
			$this->assertEquals( $original_page_item_trid,
			                     $translated_page_item_trid );
		} else {
			$this->assertNotEquals( $original_page_item_trid,
			                        $translated_page_item_trid );
		}
	}
}