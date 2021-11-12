<?php

namespace MediaWiki\Extension\Workflows\Definition;

interface IDefinitionParser {
	/**
	 * @param mixed $input
	 * @return WorkflowDefinition
	 */
	public function parse( $input ): WorkflowDefinition;
}
