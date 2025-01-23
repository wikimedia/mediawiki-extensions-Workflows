<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use DateTime;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\IUserInteractiveActivityDescriptor;
use MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview\ActivityTask;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Message\Message;
use Psr\Log\LoggerInterface;

class GenericUIActivityDescriptor extends GenericDescriptor implements IUserInteractiveActivityDescriptor {

	public function __construct(
		UserInteractiveActivity $activity, LoggerInterface $logger, ?IContextSource $context = null
	) {
		parent::__construct( $activity, $logger, $context );
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
		$diff = $due->diff( $now )->days;
		if ( $now > $due ) {
			return $diff * -1;
		}
		return $diff;
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

	public function jsonSerialize(): array {
		return parent::jsonSerialize() + [
			'alertMessage' => $this->getAlertText()->parse(),
			'completeButtonMessage' => $this->getCompleteButtonText()->parse(),
			'dueDate' => $this->getDueDate(),
			'dueDateProximity' => $this->getDueDateProximity(),
		];
	}
}
