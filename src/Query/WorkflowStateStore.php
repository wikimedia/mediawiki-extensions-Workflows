<?php

namespace MediaWiki\Extension\Workflows\Query;

use EventSauce\EventSourcing\Consumer;
use MediaWiki\Extension\Workflows\Storage\ReplayConsumer;
use User;

interface WorkflowStateStore extends ReplayConsumer, Consumer {

	public function all(): WorkflowStateStore;

	public function active(): WorkflowStateStore;

	public function onEvent( $event ): WorkflowStateStore;

	public function initiatedByUser( User $user ): WorkflowStateStore;

	public function complexQuery( $filter ): array;

	public function query(): array;
}
