<?php

namespace MediaWiki\Extension\Workflows\Query;

use EventSauce\EventSourcing\Consumer;
use MediaWiki\Extension\Workflows\Storage\ReplayConsumer;
use MediaWiki\User\User;

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
	 * @param string $event
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

	/**
	 * @param array $ids
	 *
	 * @return WorkflowStateModel[]
	 */
	public function modelsFromIds( array $ids ): array;

	/**
	 * Array of fieldName => direction pairs
	 * @param array $sort
	 * @return void
	 */
	public function setSort( array $sort ): void;
}
