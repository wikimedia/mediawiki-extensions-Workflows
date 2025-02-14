<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Action;

class EditTriggerSourceAction extends \EditAction {

	/**
	 * @return string
	 */
	public function getName() {
		return 'edittriggersource';
	}

	/**
	 * @return string
	 */
	public function getRestriction() {
		return 'workflows-admin';
	}
}
