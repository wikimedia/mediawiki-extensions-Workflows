<?php

namespace MediaWiki\Extension\Workflows;

use JsonSerializable;

interface ITrigger extends JsonSerializable {
	/**
	 * Execute the trigger - usually start the workflow
	 * @return bool
	 */
	public function trigger(): bool;

	/**
	 * Unique trigger ID
	 * @return string
	 */
	public function getId(): string;

	/**
	 * Trigger name
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return string
	 */
	public function getDescription(): string;

	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * Get the root type (parent type)
	 * Not the best implementation, still thinking about it
	 * @return string
	 */
	public function getNativeType(): string;

	/**
	 * @return array
	 */
	public function getAttributes(): array;

	/**
	 * @return array
	 */
	public function getRuleset(): array;

	/**
	 * @return bool
	 */
	public function isActive(): bool;

	/**
	 * @param array $qualifyingData Any data that may affect the decision
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool;
}
