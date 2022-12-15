<?php

namespace MediaWiki\Extension\Workflows\Definition;

use JsonSerializable;

/**
 * Very basic implementation
 */
class DefinitionContext implements JsonSerializable {
	/** @var array */
	private $items;

	/**
	 * @param array $data
	 */
	public function __construct( array $data = [] ) {
		$this->items = $this->convertData( $data );
	}

	/**
	 * Get all available keys
	 *
	 * @return array
	 */
	public function getItemKeys(): array {
		return array_keys( $this->items );
	}

	/**
	 * Get value of particular key
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed|null
	 */
	public function getItem( $key, $default = null ) {
		if ( isset( $this->items[$key] ) ) {
			return $this->items[$key];
		}

		return $default;
	}

	/**
	 * @return array
	 */
	public function getAllItems() {
		return $this->items;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setItem( $key, $value ) {
		$this->items[$key] = $value;
	}

	/**
	 * Make sure input data satisfies context requirements
	 *
	 * @param array $data
	 * @return bool
	 */
	public function verifyInputData( $data ) {
		foreach ( $this->getMissingDataKeys() as $missing ) {
			if ( !isset( $data[$missing] ) || $data[$missing] === '' ) {
				// At least one empty data key found
				return false;
			}
		}

		return true;
	}

	/**
	 * Filter out any data keys that are not specified in context
	 *
	 * @param array $data
	 * @return array
	 */
	public function filterRequiredData( $data ) {
		$filtered = [];
		foreach ( $data as $key => $value ) {
			if ( isset( $this->items[$key] ) ) {
				$filtered[$key] = $value;
			}
		}

		return $filtered;
	}

	/**
	 * Get data keys that are still missing from the context
	 *
	 * @return array
	 */
	public function getMissingDataKeys(): array {
		$missing = [];
		foreach ( $this->items as $item => $value ) {
			if ( $value === '' ) {
				$missing[] = $item;
			}
		}

		return $missing;
	}

	/**
	 * Basic conversion to correct types
	 *
	 * @param array $data
	 * @return array
	 */
	private function convertData( array $data ) {
		array_walk( $data, static function ( &$value, $key ) {
			if ( $value === '' ) {
				return;
			}
			if ( in_array( strtolower( $value ), [ 'true', 'false' ] ) ) {
				if ( $value === 'false' ) {
					$value = false;
				} else {
					$value = true;
				}
			}
			if ( is_numeric( $value ) ) {
				$value = $value + 0;
			}
		} );

		return $data;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return $this->items;
	}
}
