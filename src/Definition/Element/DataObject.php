<?php

namespace MediaWiki\Extension\Workflows\Definition\Element;

use MediaWiki\Extension\Workflows\Definition\IDataObject;

class DataObject extends Element implements IDataObject {
	/** @var array */
	protected $data = [];
	/** @var bool */
	protected $isCollection;

	/**
	 * @return static
	 */
	public static function newEmpty() {
		return new static( 'empty', [] );
	}

	public function __construct( $id, $data, $isCollection = false, $name = null ) {
		parent::__construct( $id, $name );
		$this->data = $data;
		$this->isCollection = $isCollection;
	}

	public function getData(): array {
		return $this->data;
	}

	public function setData( array $data ) {
		// Only set supported data keys
		foreach ( $this->data as $key => $value ) {
			if ( isset( $data[$key] ) ) {
				$this->data[$key] = $data[$key];
				unset( $data[$key] );
			}
		}
	}

	public function isCollection(): bool {
		return $this->isCollection;
	}

	public function getElementName(): string {
		return 'dataObject';
	}
}
