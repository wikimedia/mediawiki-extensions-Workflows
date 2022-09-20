<?php

namespace MediaWiki\Extension\Workflows\Activity\VoteActivity;

use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\VoteActivity\Action\ActionList;
use MediaWiki\Extension\Workflows\Activity\VoteActivity\Notification\VoteDelegate;
use MediaWiki\Extension\Workflows\ActivityDescriptor\UserVoteDescriptor;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\MediaWiki\Notification\TaskAssigned;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\MediaWikiServices;
use Message;
use User;

class UserVoteActivity extends GenericVoteActivity {
	/** @var bool */
	private $allowDelegation;

	/**
	 * @inheritDoc
	 */
	protected $activityKey = 'user-vote';

	/**
	 * @inheritDoc
	 */
	protected function getSpecialLogAction( string $vote ): string {
		return 'uservote-' . $vote;
	}

	/**
	 * @inheritDoc
	 */
	public function start( $data, WorkflowContext $context ) {
		$this->setPrimaryData( $data, $context );
		$this->logger->info( "User {$this->actor->getName()} is assigned to vote on {$this->targetPage->getText()}" );
	}

	/**
	 * @inheritDoc
	 */
	protected function setSecondaryData( array $data, WorkflowContext $context ): void {
		parent::setSecondaryData( $data, $context );
		$this->allowDelegation = isset( $data['allow_delegation'] ) ?
			(bool)$data['allow_delegation'] : true;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$this->setPrimaryData( $data, $context );
		$this->setSecondaryData( $data, $context );
		if ( !$this->actor instanceof User ) {
			throw new WorkflowExecutionException( 'workflows-user-vote-actor-invalid' );
		}

		switch ( $this->action ) {
			case ActionList::ACTION_VOTE:
				$vote = $data['vote'];
				$comment = $data['comment'] ?? '';

				$this->doVote( $vote, $comment );

				break;
			case ActionList::ACTION_DELEGATE:
				if ( !$this->allowDelegation ) {
					throw new WorkflowExecutionException( 'workflows-user-vote-cannot-delegate' );
				}
				$userFactory = MediaWikiServices::getInstance()->getUserFactory();
				$delegateToUser = $userFactory->newFromName( $data['delegate_to'] );

				if ( !$delegateToUser instanceof User || !$delegateToUser->getId() ) {
					$errorMessage = Message::newFromKey( 'workflows-delegate-user-invalid' )->text();
					$this->logger->error( $errorMessage );
					throw new WorkflowExecutionException( $errorMessage, $this->task );
				}

				$delegateToUsername = $data['delegate_to'];
				$delegateToComment = $data['delegate_comment'];

				$this->logger->info( "User {$this->actor->getName()} delegated vote to $delegateToUsername" );

				$this->getSpecialLogLogger()->addEntry(
					'uservote-delegate',
					$this->targetPage,
					$this->actor ?? $this->logActor,
					$delegateToComment,
					[
						'4::delegatee' => $delegateToUsername
					]
				);

				$delegateNotification = new VoteDelegate(
					$this->actor,
					$this->targetPage,
					$this->owner,
					$this->getActivityDescriptor()->getActivityName()->parse(),
					$data['comment'] ?? '',
					$delegateToUser
				);
				$assignNotification = new TaskAssigned(
					[ $delegateToUser ],
					$this->targetPage,
					$this->getActivityDescriptor()->getActivityName()->parse()
				);

				$this->getNotifier()->notify( $delegateNotification );
				$this->getNotifier()->notify( $assignNotification );

				$data['action'] = ActionList::ACTION_VOTE;
				return new ExecutionStatus\IntermediateExecutionStatus( $data );
		}

		return new ExecutionStatus( IActivity::STATUS_COMPLETE, $data );
	}

	/**
	 * @inheritDoc
	 */
	public function getTargetUsers( array $properties ): ?array {
		if ( !empty( $properties['delegate_to'] ) ) {
			return explode( ',', $properties['delegate_to'] );
		}
		$users = $properties['assigned_user'];
		return explode( ',', $users );
	}

	/**
	 * @return UserInteractionModule
	 */
	public function getUserInteractionModule(): UserInteractionModule {
		return new UserInteractionModule(
			[ 'ext.workflows.activity.vote', 'ext.oOJSPlus.formelements' ],
			'workflows.object.form.UserVote'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getActivityDescriptor(): IActivityDescriptor {
		return new UserVoteDescriptor( $this, $this->logger );
	}
}
