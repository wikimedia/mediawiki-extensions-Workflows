<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Definition\Element\EndEvent;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\Event\GatewayDecisionMade;
use MediaWiki\Extension\Workflows\Storage\Event\TaskCompleted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskCompletionStarted;
use MediaWiki\Extension\Workflows\Storage\Event\TaskStarted;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowEnded;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowInitialized;
use MediaWiki\Extension\Workflows\Storage\Event\WorkflowStarted;
use MediaWiki\Extension\Workflows\Tests\DefinitionRepository\TestDefinitionRepository;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Workflow
 */
class WorkflowProcessTest extends MediaWikiIntegrationTestCase {
	/** @var TestDefinitionRepository */
	protected $defRepository;

	public function setUp(): void {
		$this->defRepository = new TestDefinitionRepository();
		\RequestContext::getMain()->setUser( $this->getTestUser( 'sysop' )->getUser() );
	}

	/**
	 * @covers \MediaWiki\Extension\Workflows\Workflow::start()
	 * @covers \MediaWiki\Extension\Workflows\Workflow::getCurrentState()
	 */
	public function testStart() {
		$engine = Workflow::newEmpty( 'test', $this->defRepository );
		$this->assertSame( Workflow::STATE_NOT_STARTED, $engine->getCurrentState() );
		$engine->start();
		$this->assertSame( Workflow::STATE_RUNNING, $engine->getCurrentState() );
	}

	/**
	 * @covers \MediaWiki\Extension\Workflows\Workflow::completeTask()
	 * @covers \MediaWiki\Extension\Workflows\Workflow::current()
	 */
	public function testCompleteTaskPath1() {
		$engine = $this->completeWithData( [ 'actor' => 'WikiSysop', 'group' => 'Dummy' ] );
		$this->assertInstanceOf( EndEvent::class, $engine->current( 'End2' ) );
	}

	/**
	 * @covers \MediaWiki\Extension\Workflows\Workflow::completeTask()
	 * @covers \MediaWiki\Extension\Workflows\Workflow::current()
	 */
	public function testCompleteTaskPath2() {
		$engine = $this->completeWithData( [ 'actor' => 'WikiSysop', 'group' => 'default' ] );
		$this->assertInstanceOf( EndEvent::class, $engine->current( 'End1' ) );
	}

	public function testStorage() {
		$engine = $this->completeWithData( [ 'actor' => 'WikiSysop', 'group' => 'default' ] );

		$this->assertInstanceOf( WorkflowId::class, $engine->getStorage()->aggregateRootId() );
		$events = $engine->getStorage()->releaseEvents();
		$this->assertCount( 7, $events );

		$event = array_shift( $events );
		$this->assertInstanceOf( WorkflowInitialized::class, $event );
		$this->assertInstanceOf( DefinitionSource::class, $event->getDefinitionSource() );
		$this->assertInstanceOf( DefinitionContext::class, $event->getWorkflowContext() );
		$this->assertSame( true, $event->getWorkflowContext()->getItem( 'isLocal' ) );

		$event = array_shift( $events );
		$this->assertInstanceOf( WorkflowStarted::class, $event );

		$event = array_shift( $events );
		$this->assertInstanceOf( TaskStarted::class, $event );
		$this->assertSame( 'GroupVote_1', $event->getElementId() );

		$event = array_shift( $events );
		$this->assertInstanceOf( TaskCompletionStarted::class, $event );
		$this->assertSame( 'GroupVote_1', $event->getElementId() );

		$event = array_shift( $events );
		$this->assertInstanceOf( TaskCompleted::class, $event );
		$this->assertSame( 'GroupVote_1', $event->getElementId() );
		$this->assertTrue( $event->getData()['actor'] === 'WikiSysop' );
		$this->assertTrue( $event->getData()['group'] === 'default' );

		$event = array_shift( $events );
		$this->assertInstanceOf( GatewayDecisionMade::class, $event );
		$this->assertSame( 'Gateway_1ui0zp0', $event->getElementId() );
		$this->assertSame( 'GWToEnd', $event->getNextRef() );

		$event = array_shift( $events );
		$this->assertInstanceOf( WorkflowEnded::class, $event );
		$this->assertSame( 'End1', $event->getElementId() );
	}

	/**
	 * @param array $data
	 * @return Workflow
	 * @throws WorkflowExecutionException
	 */
	private function completeWithData( $data ) {
		$engine = Workflow::newEmpty( 'test', $this->defRepository );
		$engine->start();
		$current = $engine->current( 'GroupVote_1' );
		$this->assertInstanceOf( ITask::class, $current );
		$this->assertSame(
			IActivity::STATUS_STARTED,
			$engine->getActivityStatus( $engine->getActivityForTask( $current ) )
		);
		$engine->completeTask( $current, $data );
		$this->assertSame( Workflow::STATE_FINISHED, $engine->getCurrentState() );

		return $engine;
	}

	/**
	 * @throws WorkflowExecutionException
	 */
	public function testLooping() {
		$services = MediaWikiServices::getInstance();
		$loFactory = $services->getService( 'WorkflowLogicObjectFactory' );
		$loFactory->register( [ 'class' => TestActivity::class ], 'TestActivity', 'activity' );
		$engine = Workflow::newEmpty( 'looping', $this->defRepository );
		$engine->start();

		$this->assertInstanceOf( ITask::class, $engine->current( 'Activity_1n3fgk9' ) );
		for ( $i = 0; $i < 5; $i++ ) {
			$current = $engine->current( 'Activity_1n3fgk9' );

			$activity = $engine->getActivityForTask( $current );
			$this->assertInstanceOf( ITask::class, $current );
			$this->assertInstanceOf(
				IActivity::class, $activity, 'Current step should loop five times'
			);
			$engine->completeTask( $current );
		}
		// Output prop from activity successfully passed to WF context
		$this->assertSame( 5, (int)$engine->getContext()->getRunningData( 'Activity_1n3fgk9', 'loop' ) );

		$this->assertInstanceOf(
			EndEvent::class, $engine->current( 'Event_1iybc6h' ), 'Workflow should end with an EndEvent'
		);

		$this->assertSame( Workflow::STATE_FINISHED, $engine->getCurrentState() );
	}
}
