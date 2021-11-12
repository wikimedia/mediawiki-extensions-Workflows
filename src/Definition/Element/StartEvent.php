<?php

namespace MediaWiki\Extension\Workflows\Definition\Element;

use MediaWiki\Extension\Workflows\Definition\IEvent;

class StartEvent extends Element implements IEvent {

	/**
	 * @param string $id
	 * @param string|null $name
	 * @param array $outgoing
	 */
	public function __construct( $id, $name, $outgoing ) {
		parent::__construct( $id, $name, [], $outgoing );
	}

	public function getElementName(): string {
		return 'startEvent';
	}
}
