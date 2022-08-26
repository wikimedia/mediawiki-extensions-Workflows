<?php

namespace MediaWiki\Extension\Workflows\Data;

use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Linker\LinkRenderer;
use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;
use User;

class SecondaryDataProvider implements ISecondaryDataProvider {
	/** @var WorkflowFactory */
	private $workflowFactory;
	/** @var LinkRenderer */
	private $linkRenderer;
	/** @var \RequestContext|null */
	private $context;

	/**
	 * @param WorkflowFactory $workflowFactory
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct( WorkflowFactory $workflowFactory, LinkRenderer $linkRenderer ) {
		$this->workflowFactory = $workflowFactory;
		$this->linkRenderer = $linkRenderer;
		$this->context = \RequestContext::getMain();
	}

	/**
	 * @param \MWStake\MediaWiki\Component\DataStore\Record[] $dataSets
	 * @return \MWStake\MediaWiki\Component\DataStore\Record[]
	 * @throws \Exception
	 */
	public function extend( $dataSets ) {
		foreach ( $dataSets as &$dataSet ) {
			$title = $dataSet->get( 'page_title_object' );
			if ( $title instanceof \Title ) {
				$dataSet->set( Record::PAGE_LINK, $title->getLocalURL() );
			}
			/** @var WorkflowId $id */
			$id = $dataSet->get( Record::ID );
			$workflow = $this->workflowFactory->getWorkflow( $id );
			$dataSet->set( Record::ID, $id->toString() );
			$dataSet->set( Record::ASSIGNEE, $this->getAssignee( $workflow ) );
			$dataSet->set( Record::STATE_LABEL, $this->getStateLabel( $workflow->getCurrentState() ) );

			$startedTs = $dataSet->get( Record::START_TS );
			if ( $startedTs ) {
				$dataSet->set(
					Record::START_FORMATTED,
					$this->context->getLanguage()->userDate( $startedTs, $this->context->getUser() )
				);
			}

			$touchedTs = $dataSet->get( Record::LAST_TS );
			if ( $touchedTs ) {
				$dataSet->set(
					Record::LAST_FORMATTED,
					$this->context->getLanguage()->userDate( $touchedTs, $this->context->getUser() )
				);
			}

			$dataSet->set( Record::HAS_NOTICE, $workflow->getStateMessage() !== '' );
		}

		return $dataSets;
	}

	/**
	 * @param Workflow $workflow
	 * @return array
	 * @throws WorkflowExecutionException
	 */
	private function getAssignee( Workflow $workflow ): array {
		if ( $workflow->getCurrentState() !== Workflow::STATE_RUNNING ) {
			return [];
		}
		$current = $workflow->current();

		$assigned = [];
		$usedIds = [];
		foreach ( $current as $task ) {
			$activity = $workflow->getActivityForTask( $task );
			if ( !( $activity instanceof UserInteractiveActivity ) ) {
				continue;
			}
			$users = $workflow->getActivityManager()->getTargetUsersForActivity( $activity, true );
			foreach ( $users as $user ) {
				if ( !( $user instanceof User ) ) {
					continue;
				}
				if ( in_array( $user->getId(), $usedIds ) ) {
					continue;
				}
				$usedIds[] = $user->getId();
				$assigned[] = $this->linkRenderer->makeLink(
					$user->getUserPage(), $user->getRealName() ?: $user->getName()
				);
			}
		}

		return $assigned;
	}

	/**
	 * @param string $state
	 * @return string
	 */
	private function getStateLabel( string $state ): string {
		return $this->context->msg( 'workflows-ui-overview-details-state-' . $state )->text();
	}
}
