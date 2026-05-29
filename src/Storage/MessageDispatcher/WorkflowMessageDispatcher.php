<?php

namespace MediaWiki\Extension\Workflows\Storage\MessageDispatcher;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\MessageDispatcher;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;

class WorkflowMessageDispatcher implements MessageDispatcher {
	/** @var MessageConsumer[] */
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
			if ( !$instance instanceof MessageConsumer ) {
				continue;
			}
			$dispatcher->addConsumer( $instance );
		}

		return $dispatcher;
	}

	public function dispatch( Message ...$messages ): void {
		foreach ( $messages as $message ) {
			foreach ( $this->consumers as $consumer ) {
				$consumer->handle( $message );
			}
		}
	}

	public function addConsumer( MessageConsumer $consumer ) {
		$this->consumers[] = $consumer;
	}

	/**
	 * @return bool
	 */
	public function hasConsumers(): bool {
		return !empty( $this->consumers );
	}
}
