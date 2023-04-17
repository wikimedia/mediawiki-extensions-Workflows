<?php

namespace MediaWiki\Extension\Workflows;

interface IDescribedActivity {

	/**
	 * Used for presenting the activity on the UI
	 *
	 * @return IActivityDescriptor
	 */
	public function getActivityDescriptor(): IActivityDescriptor;
}
