<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ActorTrait;
use Ramsey\Uuid\UuidInterface;
use User;

final class WorkflowInitialized extends Event {
	use ActorTrait;

	/** @var DefinitionSource */
	private $definitionSource;
	/** @var DefinitionContext */
	private $workflowContext;

	/**
	 * @param UuidInterface $id
	 * @param User $actor
	 * @param DefinitionSource $definitionSource
	 * @param DefinitionContext $workflowContext
	 */
	public function __construct(
		UuidInterface $id, User $actor, DefinitionSource $definitionSource, $workflowContext
	) {
		parent::__construct( $id );
		$this->actor = $actor;
		$this->definitionSource = $definitionSource;
		$this->workflowContext = $workflowContext;
	}

	public function getDefinitionSource() {
		return $this->definitionSource;
	}

	public function getWorkflowContext(): DefinitionContext {
		return $this->workflowContext;
	}

	public function toPayload(): array {
		return array_merge( [
			'definitionSource' => $this->definitionSource,
			'actor' => $this->actorToPayload(),
			'workflowContext' => $this->workflowContext,
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			$data['id'],
			static::actorFromPayload( $payload ),
			DefinitionSource::newFromArray( $payload['definitionSource'] ),
			new DefinitionContext( $payload['workflowContext'] ),
		];
	}
}
