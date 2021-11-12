<?php

namespace MediaWiki\Extension\Workflows\Rest;

use Exception;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Definition\Repository\IDefinitionRepository;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use Wikimedia\ParamValidator\ParamValidator;

class StartHandler extends JSONBodyActionHandler {
	public function doExecute() {
		$engine = $this->getWorkflowEngine();

		$engine->start( $this->getBodyData( 'startData' ) );
		$initData = $this->getBodyData( 'initData', null );
		$initializer = $this->getInitializer( $engine );
		if ( $initData && $initializer ) {
			$engine->completeTask( $initializer->getTask(), $initData );
		}
		$this->getWorkflowFactory()->persist( $engine );

		return $this->getResponseFactory()->createJson( [
			'id' => $engine->getStorage()->aggregateRootId()->toString()
		] );
	}

	protected function getWorkflowEngine() {
		$definition = $this->getParameter( 'id' );
		$repository = $this->getParameter( 'repository' );

		return $this->getWorkflowFactory()->newEmpty( $definition, $repository );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return array_merge( parent::getParamSettings(), [
			'repository' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
			],
		] );
	}

	/**
	 * @param string $name
	 * @param IDefinitionRepository $repository
	 * @return bool
	 * @throws Exception
	 */
	protected function isValidDefinition( $name, $repository ) {
		return in_array(
			$name,
			$repository->getAllKeys()
		);
	}

	/**
	 * Get the initializer
	 *
	 * @param Workflow $engine
	 * @return UserInteractiveActivity|null if no initializer is present
	 * @throws Exception
	 */
	protected function getInitializer( Workflow $engine ) {
		$currentTasks = $engine->current();
		foreach ( $currentTasks as $id => $item ) {
			if ( $item instanceof ITask ) {
				$activity = $engine->getActivityForTask( $item );
				if ( $activity instanceof UserInteractiveActivity && $activity->isInitializer() ) {
					return $activity;
				}
			}
		}

		return null;
	}
}
