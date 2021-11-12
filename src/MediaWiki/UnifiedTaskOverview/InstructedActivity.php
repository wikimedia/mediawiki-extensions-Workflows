<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview;

use Message;

class InstructedActivity extends ActivityTask {

	protected function getBodyMessages() {
		$messages = parent::getBodyMessages();

		$properties = $this->workflow->getActivityManager()
			->getActivityProperties( $this->activity );
		$instructions = $properties['instructions'] ?? '';
		if ( !$instructions ) {
			return $messages;
		}
		if ( strlen( $instructions ) > 200 ) {
			$instructions = substr( $instructions, 0, 197 ) . '...';
		}

		$messages[] = Message::newFromKey( 'workflows-uto-body-field-instructions', $instructions );
		return $messages;
	}
}
