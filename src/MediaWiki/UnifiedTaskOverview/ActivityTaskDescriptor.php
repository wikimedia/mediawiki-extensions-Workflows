<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview;

use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MWStake\MediaWiki\Component\Utils\DisplayTitleHelper;
use stdClass;
use Throwable;

class ActivityTaskDescriptor implements ITaskDescriptor {

	/** @var UserInteractiveActivity */
	protected $activity;
	/** @var Workflow */
	protected $workflow;
	/** @var Title|null */
	protected $title = null;
	/** @var Revision|null */
	protected $revision = null;
	/** @var DisplayTitleHelper */
	private DisplayTitleHelper $displayTitleHelper;

	/**
	 * @param UserInteractiveActivity $activity
	 * @param Workflow $workflow
	 */
	public function __construct( UserInteractiveActivity $activity, Workflow $workflow ) {
		$this->activity = $activity;
		$this->workflow = $workflow;
		$utilFactory = MediaWikiServices::getInstance()->getService( 'MWStakeCommonUtilsFactory' );
		$this->displayTitleHelper = $utilFactory->getDisplayTitleHelper();

		$this->trySetTitle();
	}

	/**
	 * @param stdClass $row
	 * @return static|null
	 */
	public static function newFromTaskRow( stdClass $row ): ?static {
		$services = MediaWikiServices::getInstance();
		/** @var WorkflowFactory */
		$workflowFactory = $services->getService( 'WorkflowFactory' );

		[ $workflowIdStr, $taskElementId ] = explode( ':', $row->uto_key, 2 );

		try {
			$workflow = $workflowFactory->getWorkflow( WorkflowId::fromString( $workflowIdStr ) );
			$element = $workflow->current( $taskElementId );
			if ( !$element instanceof ITask ) {
				return null;
			}

			$activity = $workflow->getActivityForTask( $element );
			if ( !$activity instanceof UserInteractiveActivity ) {
				return null;
			}

			return new static( $activity, $workflow );
		} catch ( Throwable ) {
			return null;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getUniqueKey(): string {
		$workflowId = $this->workflow->getStorage()->aggregateRootId()->toString();
		return $workflowId . ':' . $this->activity->getTask()->getId();
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): Title {
		return $this->title;
	}

	protected function trySetTitle() {
		$title = $this->workflow->getContext()->getContextPage();
		if ( $title instanceof Title ) {
			$this->title = $title;
			$this->revision = $this->workflow->getContext()->getDefinitionContext()->getItem( 'revision' );
		}
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return 'workflows-activity-' . $this->getActivityType();
	}

	/**
	 * @return string
	 */
	public function getURL(): string {
		$query = [];
		if ( $this->revision !== null ) {
			$query['oldid'] = (int)$this->revision;
		}
		return $this->title ? $this->title->getFullURL( $query ) : '#';
	}

	/**
	 * @return Message
	 */
	public function getHeader(): Message {
		if ( !$this->title ) {
			return new RawMessage( '' );
		}
		return new RawMessage(
			$this->displayTitleHelper->getDisplayTitle( $this->title ) ?? $this->title->getSubpageText()
		);
	}

	/**
	 * @return Message
	 */
	public function getSubHeader(): Message {
		return new RawMessage( $this->workflow->getDefinition()->getSource()->getTitle() );
	}

	/**
	 * @return Message
	 */
	public function getBody(): Message {
		$messages = $this->getBodyMessages();
		$body = [];
		foreach ( $messages as $message ) {
			if ( $message instanceof Message ) {
				$body[] = $message->text();
			}
			if ( is_string( $message ) ) {
				$body[] = $message;
			}
		}

		return new RawMessage(
			implode( "\n", array_map( static function ( $a ) {
				return '* ' . $a;
			}, $body ) )
		);
	}

	/**
	 * Array of messages to be inserted to body
	 * @return array
	 */
	protected function getBodyMessages() {
		$initiator = $this->workflow->getContext()->getInitiator();
		if ( !$initiator ) {
			return [];
		}

		return [
			Message::newFromKey( 'workflows-uto-activity-body-initiator', $initiator, $initiator->getName() )
		];
	}

	/**
	 * @return int
	 */
	public function getSortKey(): int {
		return 10;
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [ 'ext.workflows.uto.styles' ];
	}

	/**
	 * @return string
	 */
	private function getActivityType(): string {
		return $this->activity->getTask()->getExtensionElements()['type'] ?? 'generic';
	}

}
