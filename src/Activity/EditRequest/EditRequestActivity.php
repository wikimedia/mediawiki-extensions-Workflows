<?php

namespace MediaWiki\Extension\Workflows\Activity\EditRequest;

use MediaWiki\Extension\Workflows\Activity\UIActivity;
use MediaWiki\Extension\Workflows\ActivityDescriptor\EditRequestDescriptor;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\UserInteractionModule;

class EditRequestActivity extends UIActivity {

	/**
	 * @return UserInteractionModule
	 */
	public function getUserInteractionModule(): UserInteractionModule {
		return new UserInteractionModule(
			[ 'ext.workflows.activity.edit.request' ],
			'workflows.object.form.EditRequest'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getActivityDescriptor(): IActivityDescriptor {
		return new EditRequestDescriptor( $this, $this->logger );
	}
}
