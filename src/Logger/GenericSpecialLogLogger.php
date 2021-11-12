<?php

namespace MediaWiki\Extension\Workflows\Logger;

use ManualLogEntry;
use Title;
use User;

class GenericSpecialLogLogger implements ISpecialLogLogger {

	/**
	 * @inheritDoc
	 */
	public function addEntry( $action, Title $target, User $actor, $comment, array $params = []	) {
		$logEntry = new ManualLogEntry( 'ext-workflows', $action );
		$logEntry->setPerformer( $actor );
		$logEntry->setTarget( $target );
		$logEntry->setComment( $comment );

		$logEntry->setParameters( $params );

		$logId = $logEntry->insert();

		$logEntry->publish( $logId );
	}

}
