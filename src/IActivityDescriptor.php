<?php

namespace MediaWiki\Extension\Workflows;

use JsonSerializable;
use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use Message;

interface IActivityDescriptor extends JsonSerializable {
	/**
	 * Get text for the AlertBanner
	 *
	 * @return Message
	 */
	public function getAlertText(): Message;

	/**
	 * Text for the "complete task" button
	 *
	 * @return Message
	 */
	public function getCompleteButtonText(): Message;

	/**
	 * @return Message
	 */
	public function getActivityName(): Message;

	/**
	 * Get UI-friendly due date
	 *
	 * @return string|null if
	 */
	public function getDueDate();

	/**
	 * How many days to due date
	 * @return int|null
	 */
	public function getDueDateProximity();

	/**
	 * Get UnifiedTaskOverview task descriptor
	 *
	 * @param Workflow $workflow
	 * @return ITaskDescriptor
	 */
	public function getTaskDescriptor( Workflow $workflow ): ITaskDescriptor;
}
