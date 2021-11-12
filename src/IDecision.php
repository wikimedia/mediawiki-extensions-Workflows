<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;

interface IDecision {

	/**
	 * Decide on which branch will flow continue on
	 *
	 * @param array $data Data coming from previous element
	 * @param WorkflowDefinition $process
	 * @return string Identifier of the flow to take
	 */
	public function decideFlow( $data, WorkflowDefinition $process ): string;

}
