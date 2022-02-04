<?php

namespace MediaWiki\Extension\Workflows\Tests\Activity\VoteActivity;

use MediaWiki\Extension\Workflows\Activity\ExecutionStatus\IntermediateExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\VoteActivity\Action\ActionList;
use MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity;
use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\Element\Task;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Logger\GenericSpecialLogLogger;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Extension\Workflows\WorkflowContextMutable;
use MediaWikiIntegrationTestCase;
use Message;
use MWStake\MediaWiki\Component\Notifications\INotifier;
use TitleFactory;
use User;

/**
 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity
 * @group Database
 */
class UserVoteActivityTest extends MediaWikiIntegrationTestCase {

	/**
	 * User vote task object
	 *
	 * @var Task
	 */
	private $task;

	/**
	 * Special log logger mock object
	 *
	 * @var ISpecialLogLogger
	 */
	private $specialLogLogger;

	/**
	 * Notifier mock object
	 *
	 * @var INotifier
	 */
	private $notifier;

	/**
	 * Actor user object.
	 * <tt>null</tt> if invalid actor is needed
	 *
	 * @var User|null
	 */
	private $actor;

	/**
	 * Owner user object
	 *
	 * @var User
	 */
	private $owner;

	/**
	 * Target page name
	 *
	 * @var string
	 */
	private $targetPage;

	/**
	 * Workflow context object.
	 * Used to set actor and is necessary for activity running
	 *
	 * @var WorkflowContext
	 */
	private $workflowContext;

	/**
	 * Sets all necessary data in properties for further testing
	 */
	private function setData() {
		$this->task = new Task(
			'Test_Id',
			'User vote',
			[],
			[],
			'userTask',
			[
				'action' => '',
				'vote' => '',
				'comment' => '',
				'delegate_to' => '',
				'delegate_to_comment' => '',
			],
			[],
			[],
			[],
			false
		);

		$this->specialLogLogger = $this->createMock( GenericSpecialLogLogger::class );

		$this->notifier = $this->createMock( INotifier::class );

		$this->actor = $this->getTestUser( [ 'actor' ] )->getUser();
		$this->owner = $this->getTestUser( [ 'owner' ] )->getUser();

		$this->targetPage = $this->getExistingTestPage()->getTitle()->getArticleID();
	}

	/**
	 * Prepares and returns user vote activity object for further testing.
	 *
	 * @return UserVoteActivity
	 */
	private function prepareActivity(): UserVoteActivity {
		$activity = new UserVoteActivity( $this->notifier, $this->task );
		$activity->setSpecialLogLogger( $this->specialLogLogger );

		$definitionContext = new DefinitionContext( [
			'pageId' => $this->targetPage
		] );

		$titleFactory = $this->createMock( TitleFactory::class );

		$mutableContext = new WorkflowContextMutable( $titleFactory );
		$mutableContext->setActor( $this->actor );
		$mutableContext->setInitiator( $this->owner );
		$mutableContext->setDefinitionContext( $definitionContext );

		$this->workflowContext = new WorkflowContext( $mutableContext );

		return $activity;
	}

	/**
	 * Case with correct user vote
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity::start()
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity::execute()
	 */
	public function testVoteCorrect() {
		$this->setData();

		$this->specialLogLogger->expects( $this->atLeastOnce() )->method( 'addEntry' );
		$this->notifier->expects( $this->atLeastOnce() )->method( 'notify' );

		$activity = $this->prepareActivity();

		$activity->start( [], $this->workflowContext );

		$data = [
			'action' => ActionList::ACTION_VOTE,
			'vote' => 'yes',
			'comment' => 'Really great article!',
		];

		$payload = $activity->execute( $data, $this->workflowContext );

		$this->assertEquals( IActivity::STATUS_COMPLETE, $payload->getStatus() );
	}

	/**
	 * Case with correct delegation of vote to another user
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity::start()
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity::execute()
	 */
	public function testDelegateCorrect() {
		$this->setData();

		$this->specialLogLogger->expects( $this->atLeastOnce() )->method( 'addEntry' );
		$this->notifier->expects( $this->atLeastOnce() )->method( 'notify' );

		$activity = $this->prepareActivity();

		$delegateToUser = $this->getTestUser( [ 'delegate_to' ] )->getUser()->getName();

		$activity->start( [], $this->workflowContext );

		$data = [
			'action' => ActionList::ACTION_DELEGATE,
			'comment' => 'Really great article!',
			'delegate_to' => $delegateToUser,
			'delegate_comment' => 'This user should review that'
		];

		$status = $activity->execute( $data, $this->workflowContext );

		$this->assertInstanceOf( IntermediateExecutionStatus::class, $status );
	}

	/**
	 * Case with delegation to non-existing user
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity::execute()
	 */
	public function testInvalidDelegateUser() {
		$this->expectExceptionMessage( Message::newFromKey( 'workflows-delegate-user-invalid' )->text() );

		$this->setData();

		$this->notifier->expects( $this->never() )->method( 'notify' );

		$activity = $this->prepareActivity();

		$activity->start( [], $this->workflowContext );

		$data = [
			'action' => ActionList::ACTION_DELEGATE,
			'comment' => 'Really great article!',
			'delegate_to' => 'Non-existing user'
		];

		$activity->execute( $data, $this->workflowContext );
	}

	/**
	 * Case with invalid user vote action specified
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity::start()
	 */
	public function testInvalidAction() {
		$this->expectExceptionMessage( Message::newFromKey( 'workflows-user-vote-action-invalid' )->text() );

		$this->setData();

		$this->specialLogLogger->expects( $this->never() )->method( 'addEntry' );
		$this->notifier->expects( $this->never() )->method( 'notify' );

		$activity = $this->prepareActivity();

		$activity->start( [], $this->workflowContext );

		$data = [
			'action' => ActionList::ACTION_INVALID,
			'vote' => 'yes',
			'comment' => 'Really great article!',
		];

		$activity->execute( $data, $this->workflowContext );
	}

	/**
	 * Case with non-existing target page specified
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\UserVoteActivity::start()
	 */
	public function testInvalidTargetPage() {
		$this->expectExceptionMessage( Message::newFromKey( 'workflows-user-vote-target-title-invalid' )->text() );

		$this->setData();

		$this->targetPage = -1;

		$activity = $this->prepareActivity();

		$activity->start( [], $this->workflowContext );
	}
}
