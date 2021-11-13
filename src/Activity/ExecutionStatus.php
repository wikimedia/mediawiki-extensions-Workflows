<?php

namespace MediaWiki\Extension\Workflows\Activity;

class ExecutionStatus {
	/** @var int */
	private $status;
	/** @var array */
	private $payload;

	public function __construct( int $status, array $payload = [] ) {
		$this->status = $status;
		$this->payload = $payload;
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function getPayload(): array {
		return $this->payload;
	}
}
