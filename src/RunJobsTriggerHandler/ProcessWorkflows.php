<?php

namespace MediaWiki\Extension\Workflows\RunJobsTriggerHandler;

use DateTime;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\DueDateProximity;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MWStake\MediaWiki\Component\Notifications\INotifier;
use MWStake\MediaWiki\Component\RunJobsTrigger\IHandler;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval;
use MWStake\MediaWiki\Component\RunJobsTrigger\Interval\OnceEveryHour;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Status;

final class ProcessWorkflows implements IHandler, LoggerAwareInterface {

	public const HANDLER_KEY = 'ext-workflows-process-workflows';

	/**
	 *
	 * @var WorkflowEventRepository
	 */
	private $workflowRepo;

	/**
	 *
	 * @var DefinitionRepositoryFactory
	 */
	private $definitionRepositoryFactory;

	/**
	 *
	 * @var LoggerInterface
	 */
	protected $logger = null;
	/** @var INotifier */
	protected $notifier;

	/**
	 *
	 * @param WorkflowEventRepository $workflowRepo
	 * @param DefinitionRepositoryFactory $definitionRepositoryFactory
	 * @param INotifier $notifier
	 */
	public function __construct(
		WorkflowEventRepository $workflowRepo, DefinitionRepositoryFactory $definitionRepositoryFactory,
		INotifier $notifier
	) {
		$this->workflowRepo = $workflowRepo;
		$this->definitionRepositoryFactory = $definitionRepositoryFactory;
		$this->logger = new NullLogger();
		$this->notifier = $notifier;
	}

	/**
	 * @inheritDoc
	 */
	public function run() {
		$status = Status::newGood();

		$workflowIds = $this->workflowRepo->retrieveAllIds();
		foreach ( $workflowIds as $workflowId ) {
			$this->logger->debug( "Loading '{id}'", [ 'id' => $workflowId->toString() ] );
			// Just the act of loading the workflow will probe any activity it might be on
			// and automatically preserve changes in case of status update
			$workflow = Workflow::newFromInstanceID(
				$workflowId, $this->workflowRepo, $this->definitionRepositoryFactory
			);
			if ( !$workflow instanceof Workflow ) {
				continue;
			}

			// Next part checks for due dates
			$current = $workflow->current();
			foreach ( $current as $element ) {
				if ( !$element instanceof ITask ) {
					continue;
				}
				$activity = $workflow->getActivityForTask( $element );
				if ( !$activity instanceof UserInteractiveActivity ) {
					continue;
				}
				if ( $this->dueDateClose( $activity ) ) {
					$this->notifyDueDateProximity( $activity, $workflow );
				}
				if ( $this->dueDateReached( $activity ) ) {
					$workflow->expireActivity( $activity );
					$workflow->persist( $this->workflowRepo );
				}
			}
		}

		return $status;
	}

	/**
	 *
	 * @return Interval
	 */
	public function getInterval() {
		return new OnceEveryHour();
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getKey() {
		return static::HANDLER_KEY;
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	private function dueDateReached( UserInteractiveActivity $activity ) {
		$dueDate = $activity->getDueDate();
		if ( !$dueDate instanceof DateTime ) {
			return false;
		}

		$now = new DateTime( "now" );
		// Dont know how else to get DT with today's date only
		$now = new DateTime( $now->format( 'd-m-Y' ) );

		return $dueDate > $now;
	}

	/**
	 * @param UserInteractiveActivity $activity
	 * @return bool
	 */
	private function dueDateClose( UserInteractiveActivity $activity ) {
		$dueDate = $activity->getDueDate();
		if ( !$dueDate instanceof DateTime ) {
			return false;
		}

		$now = new DateTime( "now" );
		if ( $dueDate->diff( $now )->days < 2 ) {
			return true;
		}

		return false;
	}

	private function notifyDueDateProximity(
		UserInteractiveActivity $activity, Workflow $workflow
	) {
		$this->notifier->notify(
			new DueDateProximity(
				$workflow->getContext()->getInitiator(),
				$workflow->getActivityManager()->getTargetUsersForActivity( $activity ),
				$workflow->getContext()->getContextPage(),
				$activity->getActivityDescriptor()->getActivityName()
			)
		);
	}
}
