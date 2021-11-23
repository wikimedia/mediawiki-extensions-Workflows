<?php

namespace MediaWiki\Extension\Workflows\Tests\Util;

use MediaWiki\Extension\Workflows\Util\DataFlattener;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Util\DataFlattener
 */
class DataFlattenerTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\Workflows\Util\DataFlattener::flatten
	 * @dataProvider provideFlattenTestData
	 * @param array $input
	 * @param array $expectedOutput
	 * @return void
	 */
	public function testFlatten( $input, $expectedOutput ) {
		$flattener = new DataFlattener();
		$output = $flattener->flatten( $input );
		$this->assertEquals( $expectedOutput, $output );
	}

	public function provideFlattenTestData() {
		return [
			'test1' => [
				'input' => [
					'someContextData' => 'value123',
					'step1' => [
						'data1' => 'value1',
						'data2' => 'value2'
					],
					'step2' => [
						'data1' => 'value1',
						'data2' => 'value2',
						'data3' => [
							[
								'nestedData1' => 'nestedValue1',
								'nestedData2' => 'nestedValue2',
								'nestedData3' => 'nestedValue3',
							],
							[
								'nestedData1' => 'nestedValue4',
								'nestedData2' => 'nestedValue5',
								'nestedData3' => 'nestedValue6',
								'nestedData4' => [
									[
										'nestedData5' => 'nestedValue7',
										'nestedData6' => 'nestedValue8',
									],
									[
										'nestedData5' => 'nestedValue9',
										'nestedData6' => 'nestedValue10',
									]
								]
							],
						]
					],
				],
				'output' => [
					'someContextData' => 'value123',
					'step1.data1' => 'value1',
					'step1.data2' => 'value2',
					'step2.data1' => 'value1',
					'step2.data2' => 'value2',
					'step2.data3._length' => 2,
					'step2.data3.0.nestedData1' => 'nestedValue1',
					'step2.data3.0.nestedData2' => 'nestedValue2',
					'step2.data3.0.nestedData3' => 'nestedValue3',
					'step2.data3.1.nestedData1' => 'nestedValue4',
					'step2.data3.1.nestedData2' => 'nestedValue5',
					'step2.data3.1.nestedData3' => 'nestedValue6',
					'step2.data3.1.nestedData4._length' => 2,
					'step2.data3.1.nestedData4.0.nestedData5' => 'nestedValue7',
					'step2.data3.1.nestedData4.0.nestedData6' => 'nestedValue8',
					'step2.data3.1.nestedData4.1.nestedData5' => 'nestedValue9',
					'step2.data3.1.nestedData4.1.nestedData6' => 'nestedValue10',
				]
			]
		];
	}

}
