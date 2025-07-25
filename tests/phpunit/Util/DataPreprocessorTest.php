<?php

namespace MediaWiki\Extension\Workflows\Tests\Util;

use MediaWiki\Extension\Workflows\Util\DataPreprocessor;
use MediaWiki\Extension\Workflows\Util\DataPreprocessorContext;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Util\DataPreprocessor
 * @group Database
 */
class DataPreprocessorTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \MediaWiki\Extension\Workflows\Util\DataPreprocessor::preprocess
	 * @dataProvider providePreprocessTestData
	 */
	public function testPreprocess( $context, $contextData, $inputData, $expectedData ) {
		$parser = $this->getServiceContainer()->getParser();
		$preprocessor = new DataPreprocessor( $parser );
		$preprocessorContext = new DataPreprocessorContext( $context['title'] );
		$outputData = $preprocessor->preprocess( $inputData, $contextData, $preprocessorContext );
		$this->assertEquals( $expectedData, $outputData );
	}

	public static function providePreprocessTestData() {
		return [
			'test1' => [
				'context' => [
					'title' => Title::newFromText( 'My cool page' )
				],
				'contextData' => [
					'step1.data2' => 'Hello',
					'step2.data1' => 'World'
				],
				'data' => [
					'text2' => '{{{step1.data2}}} {{{step2.data1}}}, {{{unset|Dude}}}!',
					'text4' => 'This is "{{FULLPAGENAME}}"',
				],
				'expectedData' => [
					'text2' => 'Hello World, Dude!',
					'text4' => 'This is "My cool page"'
				]
			]
		];
	}

}
