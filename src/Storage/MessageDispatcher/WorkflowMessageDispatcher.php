<?php

namespace MediaWiki\Extension\Workflows\Storage\MessageDispatcher;

use EventSauce\EventSourcing\Consumer;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;

class WorkflowMessageDispatcher implements MessageDispatcher {
	/** @var Consumer[] */
	private $consumers = [];

	public static function newFromRegisteredListeners() {
		$dispatcher = new static();
		$registry = ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsWorkflowListeners' );
		foreach ( $registry as $key => $spec ) {
			if ( !is_array( $spec ) ) {
				continue;
			}
			// TODO: Inject
			$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
			$instance = $objectFactory->createObject( $spec );
			if ( !$instance instanceof Consumer ) {
				continue;
			}
			$dispatcher->addConsumer( $instance );
		}

		return $dispatcher;
	}

	public function dispatch( Message ...$messages ) {
		foreach ( $messages as $message ) {
			foreach ( $this->consumers as $consumer ) {
				$consumer->handle( $message );
			}
		}
	}

	public function addConsumer( Consumer $consumer ) {
		$this->consumers[] = $consumer;
	}

	/**
	 * @return bool
	 */
	public function hasConsumers(): bool {
		return !empty( $this->consumers );
	}
}
