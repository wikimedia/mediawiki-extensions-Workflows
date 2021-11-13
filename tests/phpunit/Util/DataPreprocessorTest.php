<?php

namespace MediaWiki\Extension\Workflows\Tests\Util;

use MediaWiki\Extension\Workflows\Util\DataPreprocessor;
use MediaWiki\Extension\Workflows\Util\DataPreprocessorContext;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Title;

/**
 * @covers \MediaWiki\Extension\Workflows\Util\DataPreprocessor
 * @group Database
 */
class DataPreprocessorTest extends MediaWikiIntegrationTestCase {

	/**
	 *
	 * @return void
	 */
	public function addDBDataOnce() {
		$this->insertPage( 'SomePage', '[[SomeProperty::SomeValue]]' );
	}

	/**
	 * @covers \MediaWiki\Extension\Workflows\Util\DataPreprocessor::preprocess
	 * @dataProvider providePreprocessTestData
	 */
	public function testPreprocess( $context, $contextData, $inputData, $expectedData ) {
		$parser = MediaWikiServices::getInstance()->getParser();
		$preprocessor = new DataPreprocessor( $parser );
		$preprocessorContext = new DataPreprocessorContext( $context['title'] );
		$outputData = $preprocessor->preprocess( $inputData, $contextData, $preprocessorContext );
		$this->assertEquals( $expectedData, $outputData );
	}

	public function providePreprocessTestData() {
		return [
			'test1' => [
				'context' => [
					'title' => Title::newFromText( 'My cool page' )
				],
				'contextData' => [
					'step1.data1' => '20210709120000',
					'step1.data2' => 'Hello',
					'step2.data1' => 'World'
				],
				'data' => [
					'text1' => '{{#time:Y-m-d, H:i|{{{step1.data1}}} }}',
					'text2' => '{{{step1.data2}}} {{{step2.data1}}}, {{{unset|Dude}}}!',
					'text3' => '{{#if:{{step1.data2}}}|YES|NO}}',
					'text4' => 'This is "{{FULLPAGENAME}}"',
					'text5' => '{{#show:SomePage|?SomeProperty|link=none}}'
				],
				'expectedData' => [
					'text1' => '2021-07-09, 12:00',
					'text2' => 'Hello World, Dude!',
					'text3' => 'YES',
					'text4' => 'This is "My cool page"',
					'text5' => 'SomeValue'
				]
			]
		];
	}

}
