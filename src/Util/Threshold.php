<?php

namespace MediaWiki\Extension\Workflows\Util;

use Exception;

class Threshold {
	/** @var string */
	private $type = '';
	/** @var string */
	private $value;
	/** @var string */
	private $unit;
	/** @var Exception|null */
	private $delayedException;

	/**
	 * @param array $thresholdData
	 * @throws Exception
	 */
	public function __construct( array $thresholdData ) {
		foreach ( [ 'type', 'value', 'unit' ] as $dataKey ) {
			if ( isset( $thresholdData[$dataKey] ) ) {
				$this->$dataKey = $thresholdData[$dataKey];
			} else {
				$this->delayedException = new Exception( "Threshold data must contain \"{$dataKey}\"" );
				break;
			}
		}
	}

	/**
	 * @param array $data
	 * @param int $userCount
	 * @param string|null $keyToCheck Key in data items to check, or null if only counting
	 * @return bool
	 * @throws Exception
	 */
	public function isReached( array $data, int $userCount, ?string $keyToCheck = null ): bool {
		$this->assertNoException();
		list( $total, $count ) = $this->getProcessedAndFulfilled( $data, $keyToCheck );

		switch ( $this->unit ) {
			case 'percent':
				return $this->isPercentReached( $count, $userCount );
			case 'user':
				return $this->isUserCountReached( $count );
			default:
				throw new Exception( "Threshold unit \"{$this->unit}\" is not supported!" );
		}
	}

	/**
	 * @param array $data
	 * @param int $userCount
	 * @param string|null $keyToCheck
	 * @return bool
	 */
	public function canBeReached( array $data, int $userCount, ?string $keyToCheck = null ): bool {
		$this->assertNoException();
		if ( $this->unit !== 'user' ) {
			return true;
		}
		list( $totalCompleted, $fulfilled ) = $this->getProcessedAndFulfilled( $data, $keyToCheck );

		$needed = (int)$this->value;
		$available = $userCount - $totalCompleted;

		return $needed - $fulfilled <= $available;
	}

	/**
	 * @param array $data
	 * @param string|null $key
	 * @return int[]
	 */
	private function getProcessedAndFulfilled( $data, $key ) {
		$total = 0;
		$fulfilled = 0;
		foreach ( $data as $dataItem ) {
			$total++;
			if ( !$key ) {
				$fulfilled++;
				continue;
			}
			if ( !isset( $dataItem[$key] ) ) {
				continue;
			}
			if ( $dataItem[$key] === $this->type ) {
				$fulfilled++;
			}
		}

		return [ $total, $fulfilled ];
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		$this->assertNoException();
		return $this->type;
	}

	/**
	 * @param int $completed
	 * @param int $total
	 * @return bool
	 */
	private function isPercentReached( int $completed, int $total ): bool {
		$threshold = (int)$this->value;
		if ( $threshold < 0 ) {
			$threshold = 0;
		}
		if ( $threshold > 100 ) {
			$threshold = 100;
		}

		return ( $completed * 100 ) / $total >= $threshold;
	}

	/**
	 * @param int $completed
	 * @return bool
	 */
	private function isUserCountReached( int $completed ): bool {
		$threshold = (int)$this->value;
		return $completed >= $threshold;
	}

	/**
	 * Throw any construction-time exceptions
	 * This is delayed, in order for it to be throws during execution
	 *
	 * @throws Exception
	 */
	private function assertNoException() {
		if ( $this->delayedException instanceof Exception ) {
			throw $this->delayedException;
		}
	}
}
