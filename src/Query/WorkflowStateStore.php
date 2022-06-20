<?php

namespace MediaWiki\Extension\Workflows\Query;

use EventSauce\EventSourcing\Consumer;
use MediaWiki\Extension\Workflows\Storage\Event\Event;
use MediaWiki\Extension\Workflows\Storage\ReplayConsumer;
use User;

interface WorkflowStateStore extends ReplayConsumer, Consumer {

	/**
	 * @return WorkflowStateStore
	 */
	public function all(): WorkflowStateStore;

	/**
	 * @return WorkflowStateStore
	 */
	public function active(): WorkflowStateStore;

	/**
	 * @param Event $event
	 * @return WorkflowStateStore
	 */
	public function onEvent( $event ): WorkflowStateStore;

	/**
	 * @param User $user
	 * @return WorkflowStateStore
	 */
	public function initiatedByUser( User $user ): WorkflowStateStore;

	/**
	 * @param array $filter
	 * @param false $returnModel
	 * @return array
	 */
	public function complexQuery( $filter, $returnModel = false ): array;

	/**
	 * @param false $returnModel
	 * @return array
	 */
	public function query( $returnModel = false ): array;
}
