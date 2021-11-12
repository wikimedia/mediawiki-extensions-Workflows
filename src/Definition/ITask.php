<?php

namespace MediaWiki\Extension\Workflows\Definition;

interface ITask extends IElement {

	/**
	 * @return array|null if no properties are available
	 */
	public function getDataProperties(): ?array;

	/**
	 * @return array|null if no data input is available
	 */
	public function getInputDataAssociations(): ?array;

	/**
	 * @return array|null
	 */
	public function getOutputDataAssociations(): ?array;

	/**
	 * @return bool
	 */
	public function isLooping(): bool;

	/**
	 * Returns data for multi-instance configuration
	 *
	 * @return array|null
	 */
	public function getMultiInstanceCharacteristics(): ?array;
}
