<?php

namespace MediaWiki\Extension\Workflows;

use MWStake\MediaWiki\Component\Events\Notifier;

/**
 * Factory for creating WorkflowNotifier instances
 */
class WorkflowNotifierFactory {

	/** @var Notifier */
	private $notifier;
	/** @var ActivityManager */
	private $activityManager;

	/**
	 * @param Notifier $notifier
	 * @param ActivityManager $activityManager
	 */
	public function __construct(
		Notifier $notifier, ActivityManager $activityManager
	) {
		$this->notifier = $notifier;
		$this->activityManager = $activityManager;
	}

	/**
	 * @param Workflow $workflow
	 * @param ActivityManager|null $activityManager
	 * @return WorkflowNotifier
	 */
	public function createNotifier( Workflow $workflow, ?ActivityManager $activityManager = null ): WorkflowNotifier {
		$activityManager = $activityManager ?? $this->activityManager;
		return new WorkflowNotifier( $this->notifier, $activityManager, $workflow );
	}
}
