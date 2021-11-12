<?php

namespace MediaWiki\Extension\Workflows\Storage;

use MediaWiki\Extension\Workflows\Workflow;

/**
 * Internal use only! Do not use this class!!!
 *
 */
final class WorkflowEventDispatcher {
	/** @var array */
	private $consumers = [];
	/** @var Workflow */
	private $workflow;

	public function addConsumer( ReplayConsumer $consumer ) {
		$this->consumers[] = $consumer;
	}

	public function setWorkflow( Workflow $workflow, ...$dependencies ) {
		$this->workflow = [
			'workflow' => $workflow,
			'dependencies' => $dependencies
		];
	}

	public function dispatch( $event, $aggregateRootId ) {
		foreach ( $this->consumers as $consumer ) {
			$consumer->handleReplayEvent( $event, $aggregateRootId );
		}

		if ( !$this->workflow ) {
			return;
		}
		$workflow = $this->workflow['workflow'];
		$workflow->handleEvent( $event, $aggregateRootId, ...$this->workflow['dependencies'] );
	}
}
