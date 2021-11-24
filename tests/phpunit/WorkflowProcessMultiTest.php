<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Tests\DefinitionRepository\TestDefinitionRepository;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Workflow
 */
class WorkflowProcessMultiTest extends MediaWikiIntegrationTestCase {
	/** @var TestDefinitionRepository */
	protected $defRepository;

	public function setUp(): void {
		$this->defRepository = new TestDefinitionRepository();
		\RequestContext::getMain()->setUser( $this->getTestUser( 'sysop' )->getUser() );
	}

	/**
	 * @throws WorkflowExecutionException
	 */
	public function testParallelMulti() {
		$engine = Workflow::newEmpty( 'parallelMulti', $this->defRepository );
		$engine->start();

		$this->assertEquals( [ 'Activity_1yuv5s2', 'Activity_1dhd9wm' ], array_keys( $engine->current() ) );

		// Complete one of the parallel tasks
		$engine->completeTask( $engine->current( 'Activity_1yuv5s2' ) );
		// ...make sure the other one is still waiting on completion
		$this->assertEquals( [ 'Activity_1dhd9wm' ], array_keys( $engine->current() ) );
		// ... then complete other one
		$engine->completeTask( $engine->current( 'Activity_1dhd9wm' ) );
		// and make sure workflow is over
		$this->assertSame( Workflow::STATE_FINISHED, $engine->getCurrentState() );
	}

	/**
	 * @throws WorkflowExecutionException
	 */
	public function testParallelSingle() {
		$engine = Workflow::newEmpty( 'parallelSingle', $this->defRepository );
		$engine->start();

		$this->assertEquals( [ 'Activity_1yuv5s2_0', 'Activity_1yuv5s2_1' ], array_keys( $engine->current() ) );
		$properties1 = $engine->getActivityManager()->getActivityProperties(
			$engine->getActivityManager()->getActivityForTask( $engine->current( 'Activity_1yuv5s2_0' ) )
		);
		$properties2 = $engine->getActivityManager()->getActivityProperties(
			$engine->getActivityManager()->getActivityForTask( $engine->current( 'Activity_1yuv5s2_1' ) )
		);
		$this->assertEquals( [ 'user' => 'UserA', 'type' => 'single' ], $properties1 );
		$this->assertEquals( [ 'user' => 'UserB', 'type' => 'double' ], $properties2 );

		// Complete one of the parallel tasks
		$engine->completeTask( $engine->current( 'Activity_1yuv5s2_0' ) );
		// ...make sure the other one is still waiting on completion
		$this->assertEquals( [ 'Activity_1yuv5s2_1' ], array_keys( $engine->current() ) );
		// ... then complete other one
		$engine->completeTask( $engine->current( 'Activity_1yuv5s2_1' ) );
		// and make sure workflow is over
		$this->assertSame( Workflow::STATE_FINISHED, $engine->getCurrentState() );
	}

	public function testSequential() {
		$engine = Workflow::newEmpty( 'sequential', $this->defRepository );
		$engine->start();

		$this->assertEquals( [ 'Activity_1yuv5s2_seq_0' ], array_keys( $engine->current() ) );
		$properties = $engine->getActivityManager()->getActivityProperties(
			$engine->getActivityManager()->getActivityForTask( $engine->current( 'Activity_1yuv5s2_seq_0' ) )
		);
		$this->assertEquals( [ 'user' => 'UserA', 'type' => 'single' ], $properties );
		$engine->completeTask( $engine->current( 'Activity_1yuv5s2_seq_0' ) );
		$this->assertEquals( [ 'Activity_1yuv5s2_seq_1' ], array_keys( $engine->current() ) );
		$properties = $engine->getActivityManager()->getActivityProperties(
			$engine->getActivityManager()->getActivityForTask( $engine->current( 'Activity_1yuv5s2_seq_1' ) )
		);
		$this->assertEquals( [ 'user' => 'UserB', 'type' => 'double' ], $properties );
		$engine->completeTask( $engine->current( 'Activity_1yuv5s2_seq_1' ) );

		$this->assertSame( Workflow::STATE_FINISHED, $engine->getCurrentState() );
	}
}
