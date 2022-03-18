<?php

namespace MediaWiki\Extension\Workflows\Trigger;

class Manual extends PageRelatedTrigger {
	/** @var array */
	private $definitions;

	/**
	 * @inheritDoc
	 */
	public static function factory( $name, $data ) {
		return new static( $name, $data['definitions'], $data['active'], $data['rules'] ?? [] );
	}

	/**
	 * @param string $name
	 * @param array $definitions
	 * @param bool $active
	 * @param array $rules
	 */
	public function __construct( $name, $definitions, $active, $rules ) {
		parent::__construct( $name, 'manual', '', '', [], [], $rules, $active ?? true );
		$this->definitions = $definitions;
	}

	/**
	 * @return bool
	 */
	public function trigger(): bool {
		return true;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return 'manual';
	}

	/**
	 * @return array
	 */
	public function getAttributes(): array {
		return $this->definitions;
	}

	/**
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool {
		return $this->title instanceof \Title && $this->appliesToPage( $this->title );
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'type' => $this->getType(),
			'active' => $this->isActive(),
			'definitions' => $this->definitions,
			'rules' => $this->rules
		];
	}
}
