<?php

namespace MediaWiki\Extension\Workflows\Storage;

use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Storage\Event\Event;

interface ReplayConsumer {
	/**
	 * @param Event $event
	 * @param WorkflowId $id
	 * @return mixed
	 */
	public function handleReplayEvent( $event, WorkflowId $id );
}
