<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger;
use PHPUnit\Framework\TestCase;

class PageRelatedTriggerTest extends TestCase {
	/**
	 * @param \Title $title
	 * @param array $rules
	 * @param array $qualifyingData
	 * @param bool $shouldTrigger
	 * @dataProvider provideTriggerData
	 * @covers \MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger::shouldTrigger
	 */
	public function testShouldTrigger( $title, $rules, $qualifyingData, $shouldTrigger ) {
		$trigger = new PageRelatedTrigger( 'dummy', 'foo', '', '', [], [], $rules );
		$trigger->setTitle( $title );
		$this->assertSame( $shouldTrigger, $trigger->shouldTrigger( $qualifyingData ) );
	}

	public function provideTriggerData() {
		$title = $this->createMock( \Title::class );
		$title->method( 'getNamespace' )->willReturn( 0 );
		$title->method( 'getParentCategories' )->willReturn( [
			'Category:Test' => 1,
			'Category:Dummy' => 2
		] );
		return [
			'in-included-ns' => [
				$title,
				[
					'include' => [
						'namespace' => [ 0, 12 ]
					]
				],
				[],
				true
			],
			'not-in-included-ns' => [
				$title,
				[
					'include' => [
						'namespace' => 12
					]
				],
				[],
				false
			],
			'included-ns-but-excluded-cat' => [
				$title,
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
				$title,
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
				$title,
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
				$title,
				[
					'include' => [
						'editType' => 'major'
					]
				],
				[
					'editType' => 'minor'
				],
				false
			]
		];
	}
}
