<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview\EditRequestActivityTask;
use MediaWiki\Extension\Workflows\Workflow;
use Message;

class EditRequestDescriptor extends GenericDescriptor {
	/**
	 * @return Message
	 */
	public function getActivityName(): Message {
		return Message::newFromKey( 'workflows-activity-edit-request-name' );
	}

	/**
	 * @inheritDoc
	 */
	public function getAlertText(): Message {
		return Message::newFromKey( 'workflows-ui-alert-activity-edit-request' );
	}

	/**
	 * @inheritDoc
	 */
	public function getTaskDescriptor( Workflow $workflow ): ITaskDescriptor {
		return new EditRequestActivityTask( $this->activity, $workflow );
	}

	/**
	 * @inheritDoc
	 */
	public function getCompleteButtonText(): Message {
		return new Message( 'workflows-ui-alert-activity-edit-request-complete-button' );
	}
}
