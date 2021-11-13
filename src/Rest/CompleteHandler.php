<?php

namespace MediaWiki\Extension\Workflows\Rest;

use Exception;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\NonRecoverableWorkflowExecutionException;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Rest\HttpException;
use Wikimedia\ParamValidator\ParamValidator;

class CompleteHandler extends JSONBodyActionHandler {
	public function doExecute() {
		$workflow = $this->loadWorkflow( $this->getWorkflowId() );
		$task = $this->getTask( $workflow );
		try {
			$workflow->completeTask( $task, $this->getBodyData() );
			$this->getWorkflowFactory()->persist( $workflow );
		} catch ( NonRecoverableWorkflowExecutionException $ex ) {
			$workflow->autoAbort( 'exception', $ex->getMessage() );
			$this->getWorkflowFactory()->persist( $workflow );
			throw new HttpException( $ex->getMessage() );
		} catch ( WorkflowExecutionException $ex ) {
			throw new HttpException( $ex->getMessage() );
		} catch ( Exception $ex ) {
			throw new HttpException( $ex->getMessage() );
		}

		return $this->getResponseFactory()->createJson( [
			'ack' => true
		] );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return parent::getParamSettings() + [
			'taskId' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}

	protected function getTask( Workflow $wf ): ITask {
		$task = $wf->getTaskFromId( $this->getParameter( 'taskId' ) );
		if ( !$task ) {
			throw new HttpException(
				'No Task with id ' . $this->getParameter( 'taskId' ) . ' found!'
			);
		}

		return $task;
	}
}
