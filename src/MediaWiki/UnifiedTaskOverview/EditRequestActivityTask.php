<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview;

use MediaWiki\Message\Message;

class EditRequestActivityTask extends InstructedActivity {

	/**
	 * @inheritDoc
	 */
	public function getSubHeader(): Message {
		return Message::newFromKey(
			'workflows-uto-activity-edit-request-header'
		);
	}
}
