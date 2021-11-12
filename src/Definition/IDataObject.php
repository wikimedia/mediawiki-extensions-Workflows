<?php

namespace MediaWiki\Extension\Workflows\Definition;

interface IDataObject extends IElement {
	/**
	 * Get data from the object
	 *
	 * @return array
	 */
	public function getData(): array;

	/**
	 * @param array $data
	 * @return bool
	 */
	public function setData( array $data );

	public function isCollection(): bool;
}
