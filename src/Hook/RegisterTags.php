<?php

namespace MediaWiki\Extension\Workflows\Hook;

use MediaWiki\Extension\Workflows\Tag\MyOpenWorkflows;
use MWStake\MediaWiki\Component\GenericTagHandler\Hook\MWStakeGenericTagHandlerInitTagsHook;

class RegisterTags implements MWStakeGenericTagHandlerInitTagsHook {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeGenericTagHandlerInitTags( array &$tags ) {
		$tags[] = new MyOpenWorkflows();
	}
}
