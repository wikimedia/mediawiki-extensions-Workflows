<?php

namespace MediaWiki\Extension\Workflows\Data;

use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;

class SecondaryDataProvider implements ISecondaryDataProvider {
	/** @var WorkflowFactory */
	private $workflowFactory;
	/** @var LinkRenderer */
	private $linkRenderer;
	/** @var UserFactory */
	private $userFactory;
	/** @var \RequestContext|null */
	private $context;

	/**
	 * @param WorkflowFactory $workflowFactory
	 * @param LinkRenderer $linkRenderer
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		WorkflowFactory $workflowFactory, LinkRenderer $linkRenderer, UserFactory $userFactory
	) {
		$this->workflowFactory = $workflowFactory;
		$this->linkRenderer = $linkRenderer;
		$this->userFactory = $userFactory;
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
			if ( $title instanceof Title ) {
				$dataSet->set( Record::PAGE_LINK, $title->getLocalURL() );
			}
			/** @var WorkflowId $id */
			$id = $dataSet->get( Record::ID );
			$workflow = $this->workflowFactory->getWorkflow( $id );
			$dataSet->set( Record::ID, $id->toString() );
			$dataSet->set( Record::ASSIGNEE, $this->formatAssignee( $dataSet->get( Record::ASSIGNEE ) ) );
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

	private function formatAssignee( $assignees ) {
		$res = [];
		foreach ( $assignees as $assignee ) {
			$bits = explode( '#', $assignee );
			$username = array_shift( $bits );
			$user = $this->userFactory->newFromName( $username );
			if ( $user instanceof \User ) {
				$res[] = $this->linkRenderer->makeLink(
					$user->getUserPage(), $user->getRealName() ?: $user->getName()
				);
			}
		}
		return $res;
	}

	/**
	 * @param string $state
	 * @return string
	 */
	private function getStateLabel( string $state ): string {
		return $this->context->msg( 'workflows-ui-overview-details-state-' . $state )->text();
	}
}
