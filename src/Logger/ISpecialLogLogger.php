<?php

namespace MediaWiki\Extension\Workflows\Logger;

use MediaWiki\Title\Title;
use MediaWiki\User\User;

interface ISpecialLogLogger {

	/**
	 * Add a log entry
	 * @param string $action
	 * @param Title $target
	 * @param User $actor
	 * @param string $comment
	 * @param array|null $params
	 */
	public function addEntry( $action, Title $target, User $actor, $comment, array $params = []	);
}
