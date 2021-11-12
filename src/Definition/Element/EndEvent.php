<?php

namespace MediaWiki\Extension\Workflows\Definition\Element;

use MediaWiki\Extension\Workflows\Definition\IEvent;

class EndEvent extends Element implements IEvent {

	/**
	 * @param string $id
	 * @param string|null $name
	 * @param array $incoming
	 */
	public function __construct( $id, $name, $incoming ) {
		parent::__construct( $id, $name, $incoming, [] );
	}

	public function getElementName(): string {
		return 'endEvent';
	}
}
