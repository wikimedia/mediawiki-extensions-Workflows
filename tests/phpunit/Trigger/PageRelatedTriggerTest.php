<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;

class PageRelatedTriggerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param array $rules
	 * @param array $qualifyingData
	 * @param bool $shouldTrigger
	 * @dataProvider provideTriggerData
	 * @covers \MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger::shouldTrigger
	 */
	public function testShouldTrigger( $rules, $qualifyingData, $shouldTrigger ) {
		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( 0 );
		$title->method( 'getPrefixedDBkey' )->willReturn( 'PageA' );
		$title->method( 'getParentCategories' )->willReturn( [
			'Category:Test' => 1,
			'Category:Dummy' => 2
		] );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->method( 'newFromText' )->willReturnCallback( static function ( $pagename ){
			return Title::newFromDBkey( $pagename );
		} );
		$trigger = new PageRelatedTrigger(
			$titleFactoryMock, 'dummy',
			'Foo',
			'Bar',
			'foo',
			'', '',
			[], [],
			$rules
		);
		$trigger->setTitle( $title );
		$trigger->setLogger( $this->createMock( LoggerInterface::class ) );
		$this->assertSame( $shouldTrigger, $trigger->shouldTrigger( $qualifyingData ) );
	}

	public static function provideTriggerData() {
		return [
			'in-included-ns' => [
				[
					'include' => [
						'namespace' => [ 0, 12 ]
					]
				],
				[],
				true
			],
			'not-in-included-ns' => [
				[
					'include' => [
						'namespace' => 12
					]
				],
				[],
				false
			],
			'included-ns-but-excluded-cat' => [
				[
					'include' => [
						'namespace' => 12
					],
					'excluded' => [
						'category' => "Test"
					]
				],
				[],
				false
			],
			'in-category' => [
				[
					'include' => [
						'namespace' => [ 12 ],
						'category' => 'Dummy'
					]
				],
				[],
				true
			],
			'first-included-then-excluded' => [
				[
					'include' => [
						'namespace' => 0
					],
					'exclude' => [
						'category' => 'Test'
					]
				],
				[],
				false
			],
			'minor-edit' => [
				[
					'include' => [
						'editType' => 'major'
					]
				],
				[
					'editType' => 'minor'
				],
				false
			],
			'page-list-contains' => [
				[
					'include' => [
						'pages' => 'PageA|PageB'
					]
				],
				[],
				true
			],
			'page-list-does-not-contain' => [
				[
					'include' => [
						'pages' => 'PageC'
					]
				],
				[],
				false
			],
		];
	}
}
