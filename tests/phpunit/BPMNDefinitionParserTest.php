<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Definition\Element\EndEvent;
use MediaWiki\Extension\Workflows\Definition\Element\Gateway;
use MediaWiki\Extension\Workflows\Definition\Element\SequenceFlow;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Definition\Parser\BPMNDefinitionParser;
use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Definition\Parser\BPMNDefinitionParser
 */
class BPMNDefinitionParserTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\Workflows\Definition\Parser\BPMNDefinitionParser::parse()
	 */
	public function testParse() {
		$parser = new BPMNDefinitionParser(
			new DefinitionSource( 'dummyDefinition', 'testDefinition', 1 )
		);
		$process = $parser->parse( file_get_contents( __DIR__ . '/data/test.bpmn' ) );

		$this->assertInstanceOf(
			WorkflowDefinition::class, $process,
			'Return of the parser must be instance of ' . WorkflowDefinition::class
		);

		$this->assertInstanceOf( ITask::class, $process->getElementById( 'GroupVote_1' ) );
		$this->assertInstanceOf( Gateway::class, $process->getElementById( 'Gateway_1ui0zp0' ) );
		$this->assertInstanceOf( SequenceFlow::class, $process->getElementById( 'VoteToGW' ) );
		$this->assertInstanceOf( EndEvent::class, $process->getElementById( 'End1' ) );

		$this->assertCount( 1, $process->getElementsOfType( 'userTask' ) );
		$this->assertCount( 1, $process->getElementsOfType( 'task' ) );
		$this->assertCount( 1, $process->getElementsOfType( 'exclusiveGateway' ) );

		// Test context
		$context = $process->getContext();
		$this->assertSame( 'MyPage', $context->getItem( 'page' ) );
		$this->assertSame( true, $context->getItem( 'isLocal' ) );

		// Test task data
		$task = $process->getElementById( 'GroupVote_1' );
		$this->assertSame( 'Execute group vote', $task->getName() );
		$this->assertSame( 'userTask', $task->getElementName() );

		$extensionElements = $task->getExtensionElements();
		$this->assertSame( [
			[
				'name' => 'yes',
				'value' => '50',
				'unit' => 'percent'
			],
			[
				'name' => 'no',
				'value' => '3',
				'unit' => 'user'
			],
			[
				'name' => 'limit',
				'value' => [
					'key1' => '1',
					'key2' => '2',
					'key3' => '3',
				],
				'unit' => [
					'subunit' => [ 'user', 'percent', 'group' ]
				]
			]
		], $extensionElements['threshold'] );
		$this->assertSame( [
			'test', 'test2', [ 's1' => 'test' ]
		], $extensionElements['serial'] );

		$this->assertCount( 1, $task->getIncoming() );
		$this->assertCount( 1, $task->getOutgoing() );
		$this->assertInstanceOf( SequenceFlow::class, $process->getElementById( $task->getIncoming()[0] ) );
		$this->assertInstanceOf( SequenceFlow::class, $process->getElementById( $task->getOutgoing()[0] ) );

		// Test gateway
		$gateway = $process->getElementById( 'Gateway_1ui0zp0' );
		$this->assertSame( 'GroupVote_1.group', $gateway->getName() );
		$this->assertSame( 'exclusiveGateway', $gateway->getElementName() );

		$this->assertCount( 1, $gateway->getIncoming() );
		$this->assertCount( 2, $gateway->getOutgoing() );
		$this->assertInstanceOf( SequenceFlow::class, $process->getElementById( $gateway->getIncoming()[0] ) );
		$this->assertInstanceOf( SequenceFlow::class, $process->getElementById( $gateway->getOutgoing()[0] ) );
		$this->assertInstanceOf( SequenceFlow::class, $process->getElementById( $gateway->getOutgoing()[1] ) );
	}
}
