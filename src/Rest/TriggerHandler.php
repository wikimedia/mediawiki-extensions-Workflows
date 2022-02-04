<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Rest\Handler;

abstract class TriggerHandler extends Handler {
	/** @var TriggerRepo */
	private $triggerRepo;

	/**
	 * @param TriggerRepo $triggerRepo
	 */
	public function __construct( TriggerRepo $triggerRepo ) {
		$this->triggerRepo = $triggerRepo;
	}

	/**
	 * @return TriggerRepo
	 */
	protected function getTriggerRepo() {
		return $this->triggerRepo;
	}
}
