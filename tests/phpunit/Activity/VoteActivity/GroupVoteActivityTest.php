<?php

namespace MediaWiki\Extension\Workflows\Tests\Activity\VoteActivity;

use MediaWiki\Extension\Workflows\Activity\VoteActivity\Action\ActionList;
use MediaWiki\Extension\Workflows\Activity\VoteActivity\GroupVoteActivity;
use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\Element\Task;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\Util\GroupDataProvider;
use MediaWiki\Extension\Workflows\Util\ThresholdChecker;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Extension\Workflows\WorkflowContextMutable;
use MediaWikiIntegrationTestCase;
use Message;
use MWStake\MediaWiki\Component\Notifications\INotifier;
use TitleFactory;
use User;

/**
 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\GroupVoteActivity
 * @group Database
 */
class GroupVoteActivityTest extends MediaWikiIntegrationTestCase {

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
	 * Actor user object
	 *
	 * @var User
	 */
	private $actor_1;

	/**
	 * Actor user object
	 *
	 * @var User|null
	 */
	private $actor_2;

	/**
	 * Actor user object
	 *
	 * @var User
	 */
	private $actor_3;

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
	 * Necessary for activity execution
	 *
	 * @var WorkflowContext
	 */
	private $workflowContext;

	/**
	 * Workflow mutable context object.
	 * Used to set different actors into workflow context
	 *
	 * @var WorkflowContextMutable
	 */
	private $mutableContext;

	/**
	 * Threshold checker mock
	 *
	 * @var ThresholdChecker
	 */
	private $thresholdCheckerMock;

	/**
	 * Sets all necessary data in properties for further testing
	 */
	private function setData() {
		$this->task = new Task(
			'Test_Id',
			'Group vote',
			[],
			[],
			'userTask',
			[
				'action' => '',
				'vote' => '',
				'comment' => '',
				'group_to_vote' => '',
			],
			[],
			[],
			[
				'threshold' => [
					[
						'type' => 'yes',
						'value' => 50,
						'unit' => 'percent'
					],
					[
						'type' => 'no',
						'value' => 1,
						'unit' => 'user'
					],
				]
			],
			true
		);

		$this->specialLogLogger = $this->createMock( ISpecialLogLogger::class );

		$this->notifier = $this->createMock( INotifier::class );

		$this->actor_1 = $this->getTestUser( [ 'actor_1', 'custom_test_group' ] )->getUser();
		$this->actor_2 = $this->getTestUser( [ 'actor_2', 'custom_test_group' ] )->getUser();
		$this->actor_3 = $this->getTestUser( [ 'actor_3', 'custom_test_group' ] )->getUser();

		$this->owner = $this->getTestUser( [ 'owner' ] )->getUser();

		$this->targetPage = $this->getExistingTestPage()->getTitle()->getArticleID();
	}

	/**
	 * Prepares and returns user vote activity object for further testing.
	 *
	 * @return GroupVoteActivity
	 */
	private function prepareActivity(): GroupVoteActivity {
		$groupDataProviderMock = $this->createMock( GroupDataProvider::class );
		$groupDataProviderMock->method( 'getNumberOfUsersInGroup' )->willReturnMap( [
			[ 'test_empty_group', 0 ],
			[ 'custom_test_group', 3 ]
		] );

		$filledGroupUsers = [
			$this->actor_1->getId() => $this->actor_1->getName(),
			$this->actor_2->getId() => $this->actor_2->getName(),
			$this->actor_3->getId() => $this->actor_3->getName()
		];

		$groupDataProviderMock->method( 'getUsersInGroup' )->willReturnMap( [
			[ 'test_empty_group', [] ],
			[ 'custom_test_group', $filledGroupUsers ]
		] );

		$activity = new GroupVoteActivity(
			$this->notifier,
			$groupDataProviderMock,
			$this->task
		);
		$activity->setSpecialLogLogger( $this->specialLogLogger );

		$definitionContext = new DefinitionContext( [
			'pageId' => $this->targetPage
		] );

		$titleFactory = $this->createMock( TitleFactory::class );

		$this->mutableContext = new WorkflowContextMutable( $titleFactory );
		$this->mutableContext->setActor( $this->actor_1 );
		$this->mutableContext->setInitiator( $this->owner );
		$this->mutableContext->setDefinitionContext( $definitionContext );

		$this->workflowContext = new WorkflowContext( $this->mutableContext );

		return $activity;
	}

	/**
	 * Case with group to vote, which does not contain any users
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\GroupVoteActivity::start()
	 */
	public function testEmptyGroup() {
		$this->expectExceptionMessage( Message::newFromKey( 'workflows-group-vote-group-no-users' )->text() );

		$this->setData();

		$this->specialLogLogger->expects( $this->never() )->method( 'addEntry' );
		$this->notifier->expects( $this->never() )->method( 'notify' );

		$this->thresholdCheckerMock = $this->createMock( ThresholdChecker::class );

		$activity = $this->prepareActivity();

		$data = [
			'assigned_group' => 'test_empty_group',
		];

		$activity->execute( $data, $this->workflowContext );
	}

	/**
	 * Case there are enough "yes" votes to reach positive threshold.
	 *
	 * Group contains 3 users, 2 of them voted as "yes".
	 * As threshold for acceptance is 50% - review is accepted
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\GroupVoteActivity::execute()
	 */
	public function testThresholdYes() {
		$this->setData();

		$this->specialLogLogger->expects( $this->atLeastOnce() )->method( 'addEntry' );
		$this->notifier->expects( $this->atLeastOnce() )->method( 'notify' );

		$this->thresholdCheckerMock = $this->createMock( ThresholdChecker::class );
		$this->thresholdCheckerMock->method( 'hasReachedThresholds' )->willReturnOnConsecutiveCalls( false, true );

		$activity = $this->prepareActivity();

		$data = [
			'assigned_group' => 'custom_test_group',
		];

		// Notify users of specified group
		$activity->start( $data, $this->workflowContext );

		$data = [
			'action' => ActionList::ACTION_VOTE,
			'vote' => 'yes',
			'comment' => 'Really great article!',
			'assigned_group' => 'custom_test_group',
			'users_voted' => ''
		];

		// Users are notified and first vote is processed
		$payload = $activity->execute( $data, $this->workflowContext );

		$this->assertEquals( IActivity::STATUS_LOOP_COMPLETE, $payload->getStatus() );

		$data = $payload->getPayload();

		$this->mutableContext->setActor( $this->actor_2 );

		// Vote from second user is processed
		$payload = $activity->execute( $data, $this->workflowContext );

		// As soon as accept threshold is reached - group vote is completed
		$this->assertEquals( IActivity::STATUS_COMPLETE, $payload->getStatus() );
	}

	/**
	 * Case there are enough "no" votes to reach negative threshold.
	 *
	 * Group contains 3 users, 1 of them voted as "no".
	 * As threshold for decline is 1 user - review is declined
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\VoteActivity\GroupVoteActivity::execute()
	 */
	public function testThresholdNo() {
		$this->setData();

		$this->specialLogLogger->expects( $this->atLeastOnce() )->method( 'addEntry' );
		$this->notifier->expects( $this->atLeastOnce() )->method( 'notify' );

		$this->thresholdCheckerMock = $this->createMock( ThresholdChecker::class );
		$this->thresholdCheckerMock->method( 'hasReachedThresholds' )->willReturn( true );

		$activity = $this->prepareActivity();

		$data = [
			'assigned_group' => 'custom_test_group',
		];

		// Notify users of specified group
		$activity->start( $data, $this->workflowContext );

		$data = [
			'action' => ActionList::ACTION_VOTE,
			'vote' => 'no',
			'comment' => 'Really great article!',
			'assigned_group' => 'custom_test_group',
			'users_voted' => ''
		];

		// First vote is processed
		$payload = $activity->execute( $data, $this->workflowContext );

		// As soon as decline threshold is reached - group vote is completed
		$this->assertEquals( IActivity::STATUS_COMPLETE, $payload->getStatus() );
	}
}
