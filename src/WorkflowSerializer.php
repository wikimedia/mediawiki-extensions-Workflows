<?php

namespace MediaWiki\Extension\Workflows;

use EventSauce\EventSourcing\PointInTime;
use Exception;
use FormatJson;
use IContextSource;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use RequestContext;
use Title;

class WorkflowSerializer {
	/** @var WorkflowEventRepository */
	private $eventRepository;
	/** @var IContextSource */
	private $context;

	/**
	 * @param WorkflowEventRepository $eventRepository
	 */
	public function __construct( WorkflowEventRepository $eventRepository ) {
		$this->eventRepository = $eventRepository;
		$this->context = RequestContext::getMain();
	}

	/**
	 * @param IContextSource $context
	 */
	public function setContext( IContextSource $context ) {
		$this->context = $context;
	}

	/**
	 * @param Workflow $workflow
	 * @return array
	 */
	public function serialize( Workflow $workflow ) {
		$definitionSource = $workflow->getDefinition()->getSource();
		$initiator = null;
		if ( $workflow->getCurrentState() !== Workflow::STATE_NOT_STARTED ) {
			if ( $workflow->getContext()->getInitiator() instanceof \User ) {
				$initiator = $workflow->getContext()->getInitiator()->getName();
			}
		}
		return [
			'definition' => [
				'id' => $workflow->getDefinition()->getId(),
				'source' => $definitionSource,
				// Could be directly in the "source", but didnt want to pollute it
				'title' => $definitionSource->getTitle(),
				'desc' => $definitionSource->getDescription()
			],
			'initiator' => $initiator,
			'context' => $workflow->getContext()->getDefinitionContext(),
			'contextPage' => $workflow->getContext()->getContextPage() instanceof Title ?
				$workflow->getContext()->getContextPage()->getPrefixedDBkey() : null,
			'current' => array_keys( $workflow->current() ),
			'state' => $workflow->getCurrentState(),
			'stateMessage' => $workflow->getStateMessage(),
			'timestamps' => $this->getTimestamps( $workflow ),
			'tasks' => $this->getTasks( $workflow ),
		];
	}

	/**
	 * @param Workflow $workflow
	 * @return false|string
	 */
	public function getJSON( Workflow $workflow ) {
		return FormatJson::encode( $this->serialize( $workflow ) );
	}

	/**
	 * @param Workflow $engine
	 * @throws Exception
	 * @return array
	 */
	private function getTasks( Workflow $engine ) {
		// Get all previous and current tasks
		$tasks = $engine->getCompletedTasks();
		foreach ( $engine->current() as $id => $item ) {
			if ( $item instanceof ITask ) {
				$activity = $engine->getActivityForTask( $item );
				$tasks[$item->getId()] = $activity;
			}
		}

		$serializer = new ActivitySerializer( $engine );
		$serialized = [];
		foreach ( $tasks as $id => $activity ) {
			$serialized[$id] = $serializer->serialize( $activity );
		}

		return $serialized;
	}

	/**
	 * @param Workflow $workflow
	 * @return array
	 * @throws Exception
	 */
	private function getTimestamps( Workflow $workflow ) {
		$messages = $this->eventRepository->retrieveMessages(
			$workflow->getStorage()->aggregateRootId()
		);

		/** @var PointInTime $start */
		$start = null;
		/** @var PointInTime $last */
		$last = null;
		foreach ( $messages as $message ) {
			if ( !$start ) {
				$start = $message->timeOfRecording();
			}
			$last = $message->timeOfRecording();
		}

		$user = $this->context->getUser();
		$lang = $this->context->getLanguage();
		return [
			'start' => $last->dateTime()->format( 'YmdHis' ),
			// Unfortunate mix, but found no good way to format client-side
			'startDateAndTime' => $lang->userTimeAndDate( $start->dateTime()->format( 'YmdHis' ), $user ),
			'startDate' => $lang->userDate( $start->dateTime()->format( 'YmdHis' ), $user ),
			'last' => $last->dateTime()->format( 'YmdHis' ),
			'lastDateAndTime' => $lang->userTimeAndDate( $last->dateTime()->format( 'YmdHis' ), $user ),
			'lastDate' => $lang->userDate( $last->dateTime()->format( 'YmdHis' ), $user ),
		];
	}
}
