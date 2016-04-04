<?php

/**
 * Class Test_WPML_TM_API
 * @group wpmltm-1238
 */
class Test_ICL_AdminNotifier extends WPML_UnitTestCase {

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-1238
	 */
	public function test_message_sanitation() {
		$message = random_string( mt_rand( 10, 100 ) );

		$message .= ' `' . random_string( mt_rand( 10, 100 ) ) . '`';
		$message .= ' ' . random_string( mt_rand( 10, 100 ) );
		$message .= ' `<div>' . random_string( mt_rand( 10, 100 ) ) . '</div>`';
		$message .= ' ' . random_string( mt_rand( 10, 100 ) );
		$message .= ' `<pre>' . random_string( mt_rand( 10, 100 ) ) . '</pre>`';
		$message .= ' ' . random_string( mt_rand( 10, 100 ) );
		$message .= ' `<div><pre>' . random_string( mt_rand( 10, 100 ) ) . '</pre></div>`';
		$message .= ' ' . random_string( mt_rand( 10, 100 ) );
		$message .= ' `' . random_string( mt_rand( 10, 100 ) ) . '';

		$expected_pre_tags = $this->count_backticks_pairs( $message );

		$sanitized_message = ICL_AdminNotifier::sanitize_and_format_message( $message );

		$domSanitized_message = new DOMDocument();
		$domSanitized_message->loadHTML( $sanitized_message );

		$preTags = $domSanitized_message->getElementsByTagName( 'pre' );
		$this->assertEquals( $expected_pre_tags, $preTags->length );
	}

	/**
	 * @param $message
	 *
	 * @return int
	 */
	private function count_backticks_pairs( $message ) {
		return (int) floor( substr_count( $message, '`' ) / 2 );
	}

}