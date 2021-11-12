<?php

namespace MediaWiki\Extension\Workflows\Util;

class ThresholdCheckerFactory {
	/** @var GroupDataProvider  */
	private $groupDataProvider;

	/**
	 * @param GroupDataProvider $groupDataProvider
	 */
	public function __construct( GroupDataProvider $groupDataProvider ) {
		$this->groupDataProvider = $groupDataProvider;
	}

	/**
	 * @param array $data
	 * @return ThresholdChecker
	 * @throws \Exception
	 */
	public function makeThresholdChecker( array $data ): ThresholdChecker {
		return new ThresholdChecker( $data, $this->groupDataProvider );
	}
}
