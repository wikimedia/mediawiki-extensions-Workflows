<?php

namespace MediaWiki\Extension\Workflows;

use JsonSerializable;

class UserInteractionModule implements JsonSerializable {
	/** @var array */
	private $moduleNames = [];
	/** @var string|null */
	private $callback;
	/** @var string|null */
	private $class;
	/** @var array|null */
	private $data;

	public static function newGeneric() {
		return new static( [] );
	}

	/**
	 * @param string $definition
	 * @return static
	 */
	public static function newFromDefinitionForm( $definition ) {
		return new static( [], null, null, [
			'definitionJSON' => $definition
		] );
	}

	/**
	 * @param string|array $modules
	 * @param string|null $class
	 * @param string|null $callback
	 * @param array|null $data
	 */
	public function __construct( $modules, $class = null, $callback = null, $data = [] ) {
		if ( !is_array( $modules ) ) {
			$modules = [ $modules ];
		}
		$this->moduleNames = $modules;
		$this->class = $class;
		$this->callback = $callback;
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'modules' => $this->moduleNames,
			'callback' => $this->callback,
			'class' => $this->class,
			'data' => $this->data
		];
	}
}
