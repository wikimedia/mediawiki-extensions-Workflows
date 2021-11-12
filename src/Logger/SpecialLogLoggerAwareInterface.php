<?php

namespace MediaWiki\Extension\Workflows\Logger;

interface SpecialLogLoggerAwareInterface {

	/**
	 * @param ISpecialLogLogger $logger
	 */
	public function setSpecialLogLogger( ISpecialLogLogger $logger );

	/**
	 * @return ISpecialLogLogger
	 */
	public function getSpecialLogLogger(): ISpecialLogLogger;
}
