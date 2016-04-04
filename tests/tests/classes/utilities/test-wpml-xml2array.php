<?php

class Test_WPML_XML2Array extends WPML_UnitTestCase {

	protected $xml_single_level = <<<'EOD'
<level-1>
</level-1>
EOD;

	protected $xml_single_level_expected = array(
		'level-1' => array( 'value' => '', )
	);

	protected $xml_single_level_args = <<<'EOD'
<level-1 att="a">
</level-1>
EOD;

	protected $xml_single_level_args_expected = array(
		'level-1' =>
			array(
				'value' => '',
				'attr'  =>
					array(
						'att' => 'a',
					),
			),
	);

	protected $xml_single_level_value = <<<'EOD'
<level-1>
value
</level-1>
EOD;

	protected $xml_single_level_value_expected = array(
		'level-1' => '
value
',
	);

	protected $xml_two_level = <<<'EOD'
<level-1>
	<level-2>
	</level-2>
</level-1>
EOD;

	protected $xml_two_level_expected = array(
		'level-1' =>
			array(
				'value'   => '',
				'level-2' =>
					array(
						'value' => '',
					),
			),
	);

	protected $xml_two_level_args = <<<'EOD'
<level-1 att="a">
	<level-2 att="b">
	</level-2>
</level-1>
EOD;

	protected $xml_two_level_args_expected = array(
		'level-1' =>
			array(
				'value'   => '',
				'attr'    =>
					array(
						'att' => 'a',
					),
				'level-2' =>
					array(
						'value' => '',
						'attr'  =>
							array(
								'att' => 'b',
							),
					),
			),
	);

	protected $xml_two_level_value = <<<'EOD'
<level-1>
	<level-2>
	value
	</level-2>
</level-1>
EOD;

	protected $xml_two_level_value_expected = array(
		'level-1' =>
			array(
				'value'   => '',
				'level-2' =>
					array(
						'value' => '
	value
	',
					),
			),
	);

	protected $wpmlconfig_and_wildcards = <<<'EOD'
<wpml-config>
    <admin-texts>
        <key name="a">
            <key name="*">
                <key name="c"/>
            </key>
        </key>
        <key name="b">
            <key name="*"/>
        </key>
        <key name="c">
            <key name="*">
                <key name="d"/>
            </key>
        </key>
    </admin-texts>
</wpml-config>
EOD;

	protected $wpmlconfig_and_wildcards_expected = array(
		'wpml-config' =>
			array(
				'value'       => '',
				'admin-texts' =>
					array(
						'value' => '',
						'key'   =>
							array(
								0 =>
									array(
										'value' => '',
										'attr'  =>
											array(
												'name' => 'a',
											),
										'key'   =>
											array(
												'value' => '',
												'attr'  =>
													array(
														'name' => '*',
													),
												'key'   =>
													array(
														'value' => '',
														'attr'  =>
															array(
																'name' => 'c',
															),
													),
											),
									),
								1 =>
									array(
										'value' => '',
										'attr'  =>
											array(
												'name' => 'b',
											),
										'key'   =>
											array(
												'value' => '',
												'attr'  =>
													array(
														'name' => '*',
													),
											),
									),
								2 =>
									array(
										'value' => '',
										'attr'  =>
											array(
												'name' => 'c',
											),
										'key'   =>
											array(
												'value' => '',
												'attr'  =>
													array(
														'name' => '*',
													),
												'key'   =>
													array(
														'value' => '',
														'attr'  =>
															array(
																'name' => 'd',
															),
													),
											),
									),
							)
					)
			)
	);


	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1852
	 *
	 */
	function test_single_level() {

		$subject   = new WPML_XML2Array( $this->xml_single_level );
		$xml_array = $subject->run();

		$this->assertEqualSets( $this->xml_single_level_expected, $xml_array );
	}

	function test_two_levels() {

		$subject   = new WPML_XML2Array( $this->xml_two_level );
		$xml_array = $subject->run();

		$this->assertEqualSets( $this->xml_two_level_expected, $xml_array );
	}

	function test_single_level_args() {

		$subject   = new WPML_XML2Array( $this->xml_single_level_args );
		$xml_array = $subject->run();

		$this->assertEqualSets( $this->xml_single_level_args_expected, $xml_array );
	}

	function test_two_level_args() {

		$subject   = new WPML_XML2Array( $this->xml_two_level_args );
		$xml_array = $subject->run();

		$this->assertEqualSets( $this->xml_two_level_args_expected, $xml_array );
	}

	function test_single_level_value_and_false_attrs() {

		$subject   = new WPML_XML2Array( $this->xml_single_level_value, false );
		$xml_array = $subject->run();

		$this->assertEqualSets( $this->xml_single_level_value_expected, $xml_array );
	}

	function test_two_levels_values() {

		$subject   = new WPML_XML2Array( $this->xml_two_level_value );
		$xml_array = $subject->run();

		$this->assertEqualSets( $this->xml_two_level_value_expected, $xml_array );
	}

	function test_wpmlconfig_and_wildcards() {

		$subject   = new WPML_XML2Array( $this->wpmlconfig_and_wildcards );
		$xml_array = $subject->run();

		$this->assertEqualSets( $this->wpmlconfig_and_wildcards_expected, $xml_array );
	}
}