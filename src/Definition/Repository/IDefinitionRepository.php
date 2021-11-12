<?php

namespace MediaWiki\Extension\Workflows\Definition\Repository;

use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;

interface IDefinitionRepository {
	/**
	 * @return array
	 */
	public function getAllKeys(): array;

	/**
	 * @param string $definitionName
	 * @param int|null $version
	 * @return WorkflowDefinition
	 */
	public function getDefinition( $definitionName, ?int $version = null ): WorkflowDefinition;

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Get display title for definition key
	 *
	 * @param string $definition key
	 * @return string
	 */
	public function getDefinitionDisplayTitle( $definition ): string;

	/**
	 * Get description text for definition key
	 *
	 * @param string $definition key
	 * @return string
	 */
	public function getDefinitionDescription( $definition ): string;
}
