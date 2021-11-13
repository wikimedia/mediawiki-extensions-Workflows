<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use DateTime;
use IContextSource;
use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview\ActivityTask;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use Message;
use RequestContext;

class GenericDescriptor implements IActivityDescriptor {
	/** @var UserInteractiveActivity */
	protected $activity;
	/** @var IContextSource */
	protected $context;

	/**
	 * @param UserInteractiveActivity $activity
	 * @param IContextSource|null $context Context in which Activity is being described
	 */
	public function __construct(
		UserInteractiveActivity $activity,
		?IContextSource $context = null
	) {
		$this->activity = $activity;
		if ( !$context ) {
			// Soo nice
			$context = RequestContext::getMain();
		}
		$this->context = $context;
	}

	/**
	 * @return Message
	 */
	public function getActivityName(): Message {
		return new \RawMessage( $this->activity->getTask()->getName() );
	}

	/**
	 * @inheritDoc
	 */
	public function getAlertText(): Message {
		return Message::newFromKey( 'workflows-ui-alert-running-workflow-user-task' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDueDate() {
		$due = $this->activity->getDueDate();
		if ( $due === null ) {
			return null;
		}
		$lang = $this->context->getLanguage();
		$user = $this->context->getUser();
		return $lang->userDate( $due->format( 'YmdHis' ), $user );
	}

	/**
	 * @inheritDoc
	 */
	public function getDueDateProximity() {
		$due = $this->activity->getDueDate();
		if ( $due === null ) {
			return null;
		}
		$now = new DateTime( "now" );
		return $due->diff( $now )->days;
	}

	/**
	 * @inheritDoc
	 */
	public function getTaskDescriptor( Workflow $workflow ): ITaskDescriptor {
		return new ActivityTask( $this->activity, $workflow );
	}

	/**
	 * @inheritDoc
	 */
	public function getCompleteButtonText(): Message {
		return new Message( 'workflows-ui-alert-action-complete' );
	}

	public function jsonSerialize() {
		return [
			'name' => $this->getActivityName()->text(),
			'alertMessage' => $this->getAlertText()->parse(),
			'completeButtonMessage' => $this->getCompleteButtonText()->parse(),
			'dueDate' => $this->getDueDate(),
			'dueDateProximity' => $this->getDueDateProximity(),
		];
	}
}
