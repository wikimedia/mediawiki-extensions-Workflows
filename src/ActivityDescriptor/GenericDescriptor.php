<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use DateTime;
use IContextSource;
use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview\ActivityTask;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use Message;
use MWStake\MediaWiki\Component\Notifications\INotification;
use Psr\Log\LoggerInterface;
use RequestContext;

class GenericDescriptor implements IActivityDescriptor {
	/** @var UserInteractiveActivity */
	protected $activity;
	/** @var LoggerInterface */
	protected $logger;
	/** @var IContextSource */
	protected $context;

	/**
	 * @param UserInteractiveActivity $activity
	 * @param LoggerInterface $logger
	 * @param IContextSource|null $context Context in which Activity is being described
	 */
	public function __construct(
		UserInteractiveActivity $activity,
		LoggerInterface $logger,
		?IContextSource $context = null
	) {
		$this->activity = $activity;
		$this->logger = $logger;
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
	public function getTaskName(): Message {
		$taskName = $this->activity->getTask()->getName();

		$taskMsg = Message::newFromKey( "workflows-ui-workflow-overview-step-name-$taskName" );
		if ( !$taskMsg->exists() ) {
			$taskMsg = new \RawMessage( $taskName );
		}

		return $taskMsg;
	}

	/**
	 * @inheritDoc
	 */
	public function getLocalizedProperties( array $properties ): array {
		$propertiesTranslated = [];

		foreach ( $properties as $propertyKey => $value ) {
			$propertyMessage = Message::newFromKey( 'workflows-activity-property-' . $propertyKey );

			if ( $propertyMessage->exists() ) {
				$propertyTitle = $propertyMessage->text();
			} else {
				$propertyTitle = $propertyKey;
			}

			$propertiesTranslated[$propertyTitle] = $value;
		}

		return $propertiesTranslated;
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
		return [
			'name' => $this->getActivityName()->text(),
			'taskName' => $this->getTaskName()->text(),
			'alertMessage' => $this->getAlertText()->parse(),
			'completeButtonMessage' => $this->getCompleteButtonText()->parse(),
			'dueDate' => $this->getDueDate(),
			'dueDateProximity' => $this->getDueDateProximity(),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getNotificationFor(
		ActivityEvent $event, Workflow $workflow
	): ?INotification {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getHistoryReport( Workflow $workflow ): array {
		return [];
	}
}
