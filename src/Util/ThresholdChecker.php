<?php

namespace MediaWiki\Extension\Workflows\Util;

use Exception;

class ThresholdChecker {
	/** @var Threshold[] */
	private $thresholds;

	public function __construct(
		array $thresholds, GroupDataProvider $groupDataProvider
	) {

		if ( empty( $thresholds ) ) {
			throw new Exception( 'No thresholds available' );
		}
		if ( array_keys( $thresholds ) !== range( 0, count( $thresholds ) - 1 ) ) {
			// Check if only one threshold is there (if array is assoc)
			$thresholds = [ $thresholds ];
		}
		foreach ( $thresholds as $thresholdData ) {
			$this->thresholds[] = new Threshold( $thresholdData, $groupDataProvider );
		}
	}

	/**
	 * @param array $data
	 * @param string $groupName
	 * @param string|null $keyToCheck Key that contains threshold name, or null if just count is required
	 * @return bool
	 * @throws Exception
	 */
	public function hasReachedThresholds( array $data, string $groupName, ?string $keyToCheck = null  ): bool {
		$canBeReached = false;
		foreach ( $this->thresholds as $threshold ) {
			if ( $threshold->isReached( $data, $groupName, $keyToCheck ) ) {
				return true;
			}
			if ( $threshold->canBeReached( $data, $groupName, $keyToCheck ) ) {
				$canBeReached = true;
			}
		}

		if ( !$canBeReached ) {
			// If none is yet reached, and none can be reached
			throw new Exception( "None of the remaining thresholds can be reached" );
		}

		return false;
	}
}
