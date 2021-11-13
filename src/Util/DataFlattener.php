<?php

namespace MediaWiki\Extension\Workflows\Util;

class DataFlattener {

	/**
	 *
	 * @param array $activityData
	 * @return array
	 */
	public function flatten( $activityData ) {
		$flattened = [];
		foreach ( $activityData as $activityId => $data ) {
			foreach ( $data as $dataKey => $dataValue ) {
				$nestedDataKey = "$activityId.$dataKey";

				if ( is_array( $dataValue ) ) {
					$this->flattenArray( $flattened, $nestedDataKey, $dataValue );
				} else {
					$flattened[$nestedDataKey] = $dataValue;
				}
			}
		}

		return $flattened;
	}

	/**
	 * Recursively flattens nested arrays
	 *
	 * @param array &$resultArray
	 * @param string $initialKey
	 * @param array $dataArray
	 */
	public function flattenArray( array &$resultArray, string $initialKey, array $dataArray ): void {
		$length = 0;

		foreach ( $dataArray as $dataKey => $dataValue ) {
			$nestedDataKey = "$initialKey.$dataKey";

			if ( is_array( $dataValue ) ) {
				$this->flattenArray( $resultArray, $nestedDataKey, $dataValue );

				// Length is calculated only for lists
				if ( is_numeric( $dataKey ) ) {
					$length++;
				}
			} else {
				$resultArray[$nestedDataKey] = $dataValue;
			}
		}

		if ( $length > 0 ) {
			$resultArray["$initialKey._length"] = $length;
		}
	}
}
