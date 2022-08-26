<?php

namespace MediaWiki\Extension\Workflows\Query;

use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\Event\Event;

interface WorkflowStateModel {

	/**
	 * @return WorkflowId
	 */
	public function getWorkflowId(): WorkflowId;

	/**
	 * @return array
	 */
	public function getPayload(): array;

	/**
	 * @return string
	 */
	public function getTouched(): string;

	/**
	 * @return string
	 */
	public function getStarted(): string;

	/**
	 * @return string
	 */
	public function getState(): string;

	/**
	 * @return array
	 */
	public function serialize(): array;

	/**
	 * Update model with event data
	 *
	 * @param Event $event
	 */
	public function handleEvent( Event $event );
}
