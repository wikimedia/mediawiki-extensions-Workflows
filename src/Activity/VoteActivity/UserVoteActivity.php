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
use Message;
use User;

class UserVoteActivity extends GenericVoteActivity {

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
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$this->setPrimaryData( $data, $context );
		$this->setSecondaryData( $data, $context );

		switch ( $this->action ) {
			case ActionList::ACTION_VOTE:
				$vote = $data['vote'];
				$comment = $data['comment'] ?? '';

				$this->doVote( $vote, $comment );

				break;
			case ActionList::ACTION_DELEGATE:
				$delegateToUser = User::newFromName( $data['delegate_to'] );

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
					User::newFromName( $delegateToUsername )
				);
				$assignNotification = new TaskAssigned(
					User::newFromName( $delegateToUsername ),
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
		return new UserVoteDescriptor( $this );
	}
}
