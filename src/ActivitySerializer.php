<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Json\FormatJson;

class ActivitySerializer {
	/** @var Workflow */
	private $workflow;

	public function __construct( Workflow $workflow ) {
		$this->workflow = $workflow;
	}

	/**
	 * Serialize activity
	 *
	 * @param IActivity $activity
	 * @return array
	 */
	public function serialize( IActivity $activity ) {
		$activityManager = $this->workflow->getActivityManager();
		$properties = $activityManager->getActivityPublicProperties( $activity );
		$data = [
			'status' => $activityManager->getActivityStatus( $activity ),
			'properties' => $properties,
			'rawProperties' => $activity->getTask()->getDataProperties(),
			'isUserInteractive' => $activity instanceof UserInteractiveActivity,
			'isDescribed' => $activity instanceof IDescribedActivity,
			'isInitializer' => $activity instanceof UserInteractiveActivity && $activity->isInitializer(),
		];
		if ( $activity instanceof UserInteractiveActivity ) {
			$data['userInteractionModule'] = $activity->getUserInteractionModule();
			$data['targetUsers'] = [];
			try {
				$targetUsers = $activityManager->getTargetUsersForActivity( $activity );
				if ( $targetUsers !== null ) {
					$data['targetUsers'] = $targetUsers;
				}
			} catch ( WorkflowExecutionException $exception ) {
				// TODO: log
			}
		}
		if ( $activity instanceof IDescribedActivity ) {
			$data['description'] = $activity->getActivityDescriptor()->jsonSerialize();
			$data['displayData'] = [
				'localizedProperties' => $activity->getActivityDescriptor()->getLocalizedProperties( $properties )
			];
			$data['history'] = $activity->getActivityDescriptor()
				->getHistoryReport( $this->workflow );
		}

		return array_merge( $activity->getTask()->jsonSerialize(), $data );
	}

	/**
	 * Serialize activity to JSON
	 *
	 * @param IActivity $activity
	 * @return false|string
	 */
	public function getJSON( IActivity $activity ) {
		return FormatJson::encode( $this->serialize( $activity ) );
	}
}
