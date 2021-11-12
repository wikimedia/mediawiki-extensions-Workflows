<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview;

use Message;

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
