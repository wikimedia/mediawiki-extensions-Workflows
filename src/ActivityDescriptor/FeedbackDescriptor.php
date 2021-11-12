<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview\InstructedActivity;
use MediaWiki\Extension\Workflows\Workflow;

class FeedbackDescriptor extends GenericDescriptor {

	/**
	 * @inheritDoc
	 */
	public function getTaskDescriptor( Workflow $workflow ): ITaskDescriptor {
		return new InstructedActivity( $this->activity, $workflow );
	}
}
