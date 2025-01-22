<?php

namespace MediaWiki\Extension\Workflows\ActivityDescriptor;

use IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\IDescribedActivity;
use MediaWiki\Extension\Workflows\Storage\Event\ActivityEvent;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\Events\INotificationEvent;
use Psr\Log\LoggerInterface;

class GenericDescriptor implements IActivityDescriptor {
	/** @var UserInteractiveActivity */
	protected $activity;
	/** @var LoggerInterface */
	protected $logger;
	/** @var IContextSource */
	protected $context;

	/**
	 * @param IDescribedActivity $activity
	 * @param LoggerInterface $logger
	 * @param IContextSource|null $context Context in which Activity is being described
	 */
	public function __construct(
		IDescribedActivity $activity,
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
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'name' => $this->getActivityName()->text(),
			'taskName' => $this->getTaskName()->text()
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getNotificationFor(
		ActivityEvent $event, Workflow $workflow
	): ?INotificationEvent {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getHistoryReport( Workflow $workflow ): array {
		return [];
	}
}
