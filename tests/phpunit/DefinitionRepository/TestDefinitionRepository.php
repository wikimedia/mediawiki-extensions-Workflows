<?php

namespace MediaWiki\Extension\Workflows\Tests\DefinitionRepository;

use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Definition\Parser\BPMNDefinitionParser;
use MediaWiki\Extension\Workflows\Definition\Repository\IDefinitionRepository;
use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;
use Message;

class TestDefinitionRepository implements IDefinitionRepository {

	public function getAllKeys(): array {
		return [ 'test', 'looping', 'user_vote' ];
	}

	public function getDefinition( $definitionName, ?int $version = null ): WorkflowDefinition {
		$parser = new BPMNDefinitionParser(
			new DefinitionSource( $this->getName(), $definitionName, $version ?? 1 )
		);
		return $parser->parse( file_get_contents( dirname( __DIR__ ) . '/data/' . $definitionName . '.bpmn' ) );
	}

	public function getName(): string {
		return 'Test repository';
	}

	public function getDisplayText(): Message {
		return Message::newFromKey( 'dummy' );
	}

	public function getDefinitionDisplayTitle( $definition ): string {
		return $definition;
	}

	public function getDefinitionDescription( $definition ): string {
		return '';
	}
}
