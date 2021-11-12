<?php

namespace MediaWiki\Extension\Workflows\Exception;

use EventSauce\EventSourcing\MessageRepository;
use Exception;

class MessagePersistanceException extends Exception {

	/**
	 * @param MessageRepository $repo
	 */
	public function __construct( MessageRepository $repo ) {
		parent::__construct(
			"Failed to insert message in {repo} message repository", [
			'repo' => get_class( $repo )
		] );
	}
}
