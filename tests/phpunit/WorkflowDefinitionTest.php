<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Definition\Element\EndEvent;
use MediaWiki\Extension\Workflows\Definition\Element\SequenceFlow;
use MediaWiki\Extension\Workflows\Definition\Element\StartEvent;
use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Definition\WorkflowDefinition
 */
class WorkflowDefinitionTest extends TestCase {
	/**
	 * @covers WorkflowDefinition::addElement()
	 * @covers WorkflowDefinition::getElements()
	 * @covers WorkflowDefinition::getElementById()
	 * @dataProvider provideProcessObjects
	 */
	public function testAddElement( WorkflowDefinition $process ) {
		$this->assertInstanceOf(
			SequenceFlow::class,
			$process->getElementById( 'StartToEnd' ),
			'Element should be a sequenceFlow'
		);
		$this->assertCount(
			3, $process->getElements(), 'Process should have 3 elements'
		);
	}

	public function provideProcessObjects() {
		$process = new WorkflowDefinition(
			'test',
			new DefinitionSource( 'dummyRepo', 'dummyDef', 1 ),
			new DefinitionContext( [ 'test' => 'value' ] )
		);

		$process->addElement( new StartEvent( 'Start1', 'TestStart', [ 'StartToEnd' ] ) );
		$process->addElement( new SequenceFlow( 'StartToEnd', 'Start1', 'End1' ) );
		$process->addElement( new EndEvent( 'End1', null, [ 'End1' ] ) );

		return [
			[ $process ]
		];
	}
}
