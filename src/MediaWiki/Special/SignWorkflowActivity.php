<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Special;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Workflows\Activity\UIActivity;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\NonRecoverableWorkflowExecutionException;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Logger\GenericSpecialLogLogger;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class SignWorkflowActivity extends SpecialPage {
	/** @var array|null */
	private ?array $reauthPostData = null;

	/** @var Title|null */
	private ?Title $backToTitle = null;

	/**
	 * @param WorkflowFactory $workflowFactory
	 * @param GenericSpecialLogLogger $specialLogLogger
	 * @param LoggerInterface $logger
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly WorkflowFactory $workflowFactory,
		private readonly GenericSpecialLogLogger $specialLogLogger,
		private readonly LoggerInterface $logger,
		private readonly TitleFactory $titleFactory
	) {
		parent::__construct( 'SignWorkflowActivity', '', listed: false );
	}

	/**
	 * @param string $subPage
	 * @return void
	 * @throws WorkflowExecutionException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->checkPermissions();

		if ( $subPage && $subPage !== 'sign' ) {
			$this->invalidRequest();
			return;
		}

		$request = $this->getRequest();
		$backTo = $request->getVal( 'backTo' );
		$this->backToTitle = $backTo ? $this->titleFactory->newFromText( $backTo ) : null;

		// This endpoint is POST-only and always executes the sign action.
		// If reauthentication is needed, checkLoginSecurityLevel() redirects and
		// setReauthPostData() restores POST data on the return request.
		if ( !$this->checkLoginSecurityLevel( $this->getLoginSecurityLevel() ) ) {
			return;
		}

		$postData = $this->reauthPostData ?? $request->getPostValues();

		if ( !$request->wasPosted() && $this->reauthPostData === null ) {
			$this->getOutput()->showErrorPage(
				Message::newFromKey( 'workflows-sign-activity-invalid-request-title' ),
				Message::newFromKey( 'workflows-sign-activity-no-post-body' )
			);
			return;
		}

		$userPostedData = json_decode( $postData['activity_data'] ?? '[]', true );
		if ( !is_array( $userPostedData ) ) {
			$this->invalidRequest();
			return;
		}
		if (
			!isset( $postData['activity_data'] ) ||
			!isset( $postData['workflow_id'] ) ||
			!isset( $postData['activity_id'] )
		) {
			$this->invalidRequest();
			return;
		}

		$workflow = $this->getWorkflow( $postData['workflow_id'] ?? null );
		$task = $this->getTask( $workflow, $postData['activity_id'] ?? null );
		$activity = $workflow->getActivityManager()->getActivityForTask( $task );

		$workflow->setActor( RequestContext::getMain()->getUser() );

		if ( $activity instanceof UIActivity ) {
			$targetUsers = $workflow->getActivityManager()->getTargetUsersForActivity( $activity );
			if ( !in_array( $this->getUser()->getName(), $targetUsers ) ) {
				$this->logger->warning( 'Sign activity: user {user} not in target users: {target_users}', [
					'user' => $this->getUser()->getName(),
					'target_users' => implode( ', ', $targetUsers )
				] );
				$this->invalidRequest( 'workflows-sign-activity-invalid-user' );
				return;
			}
		}

		try {
			$workflow->signTask( $task->getId() );
			$workflow->completeTask( $task, $userPostedData );
			$this->workflowFactory->persist( $workflow );
			if ( $this->backToTitle ) {
				$this->getOutput()->redirect( $this->backToTitle->getLocalURL() );
			}
		} catch ( Throwable $ex ) {
			if ( $ex instanceof NonRecoverableWorkflowExecutionException ) {
				$workflow->autoAbort( 'exception', $ex->getMessage() );
				$this->workflowFactory->persist( $workflow );
			}

			$this->logger->error( 'Failed to complete activity after signing: {ex}', [
				'ex' => $ex->getMessage()
			] );
			$this->getOutput()->showErrorPage(
				Message::newFromKey( 'workflows-sign-activity-invalid-request-title' ),
				$ex->getMessage()
			);
		}
	}

	/**
	 * @return string
	 */
	protected function getLoginSecurityLevel() {
		return $this->getName();
	}

	protected function setReauthPostData( array $data ) {
		$this->reauthPostData = $data;
	}

	/**
	 * @param string|null $message
	 * @return void
	 */
	private function invalidRequest( ?string $message = null ): void {
		$this->getOutput()->showErrorPage(
			Message::newFromKey( 'workflows-sign-activity-invalid-request-title' ),
			Message::newFromKey( $message ?? 'workflows-sign-activity-invalid-request-body' ),
			$this->backToTitle
		);
	}

	/**
	 * @param string|null $wfId
	 * @return Workflow
	 * @throws \MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	private function getWorkflow( ?string $wfId ): Workflow {
		if ( !$wfId ) {
			$this->invalidRequest();
		}
		return $this->workflowFactory->getWorkflow( WorkflowId::fromString( $wfId ) );
	}

	/**
	 * @param Workflow $workflow
	 * @param string|null $activityId
	 * @return ITask
	 */
	private function getTask( Workflow $workflow, ?string $activityId ): ITask {
		$current = $workflow->current();

		if ( !isset( $current[$activityId] ) ) {
			$this->invalidRequest();
		}
		return $current[$activityId];
	}

}
