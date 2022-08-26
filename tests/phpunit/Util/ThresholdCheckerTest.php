<?php

namespace MediaWiki\Extension\Workflows\Tests\Util;

use Exception;
use MediaWiki\Extension\Workflows\Util\ThresholdChecker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Util\ThresholdChecker
 */
class ThresholdCheckerTest extends TestCase {

	/**
	 * Checks correct work of all available thresholds
	 *
	 * @covers \MediaWiki\Extension\Workflows\Util\ThresholdChecker::isThresholdReached()
	 * @dataProvider provideThresholdsData
	 */
	public function testThresholds(
		$thresholdData, $checkData, $checkDataKey,
		$groupUserCount, $hasReached, $invalidData
	) {
		if ( $invalidData ) {
			$this->expectException( Exception::class );
		}

		$checker = new ThresholdChecker( $thresholdData );
		$this->assertSame( $hasReached, $checker->hasReachedThresholds(
			$checkData, $groupUserCount, $checkDataKey
		) );
	}

	/**
	 * @return array[]
	 */
	public function provideThresholdsData(): array {
		return [
			'not-reached' => [
				[
					[
						'type' => 'yes',
						'value' => 75,
						'unit' => 'percent',
					]
				],
				[
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'yes'
					],
					[
						'dummy' => 0
					]
				],
				'vote',
				4,
				false,
				false
			],
			'reached-percent' => [
				[
					[
						'type' => 'yes',
						'value' => 75,
						'unit' => 'percent',
					],
					[
						'type' => 'no',
						'value' => 2,
						'unit' => 'user'
					],
				],
				[
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'no'
					]
				],
				'vote',
				4,
				true,
				false
			],
			'single-threshold' => [
				[
					'type' => 'yes',
					'value' => 50,
					'unit' => 'percent',
				],
				[
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'yes'
					]
				],
				'vote',
				3,
				true,
				false
			],
			'just-count' => [
				[
					'type' => 'limit',
					'value' => 50,
					'unit' => 'percent',
				],
				[
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'no'
					]
				],
				null,
				3,
				true,
				false
			],
			'invalid-data' => [
				[
					'value' => 50,
					'unit' => 'percent',
				],
				[],
				null,
				3,
				false,
				true
			],
			'impossible-condition' => [
				[
					[
						'type' => 'yes',
						'value' => 7,
						'unit' => 'user',
					],
					[
						'type' => 'no',
						'value' => 6,
						'unit' => 'user',
					],
				],
				[
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'yes'
					],
					[
						'vote' => 'no'
					],
					[
						'vote' => 'no'
					]
				],
				'vote',
				8,
				false,
				true
			],
		];
	}
}
