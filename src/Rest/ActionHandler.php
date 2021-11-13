<?php

namespace MediaWiki\Extension\Workflows\Rest;

use Exception;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Storage\AggregateRoot\Id\WorkflowId;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use Wikimedia\ParamValidator\ParamValidator;

abstract class ActionHandler extends Handler {
	/** @var WorkflowFactory */
	private $workflowFactory;

	/**
	 * @param WorkflowFactory $factory
	 */
	public function __construct( WorkflowFactory $factory ) {
		$this->workflowFactory = $factory;
	}

	public function execute() {
		try {
			return $this->doExecute();
		} catch ( Exception $ex ) {
			throw new HttpException( $ex->getMessage() );
		}
	}

	/**
	 * @return Response
	 */
	abstract public function doExecute();

	/**
	 * @param string $name
	 * @param string|null $default
	 * @return string
	 */
	public function getParameter( $name, $default = null ) {
		$validated = $this->getValidatedParams();
		if ( isset( $validated[$name] ) ) {
			return $validated[$name];
		}

		return $default;
	}

	/**
	 * @param WorkflowId $id
	 * @return Workflow
	 * @throws HttpException
	 */
	protected function loadWorkflow( WorkflowId $id ) {
		try {
			if ( !$this->isValidId( $id ) ) {
				throw new HttpException( "ID {$id->toString()} is not valid" );
			}

			return $this->workflowFactory->getWorkflow( $id );
		} catch ( WorkflowExecutionException $ex ) {
			throw new HttpException( $ex->getMessage() );
		}
	}

	/**
	 * @return WorkflowFactory
	 */
	protected function getWorkflowFactory(): WorkflowFactory {
		return $this->workflowFactory;
	}

	/**
	 * @return WorkflowId
	 */
	protected function getWorkflowId(): WorkflowId {
		try {
			return WorkflowId::fromString( $this->getParameter( 'id' ) );
		} catch ( Exception $ex ) {
			throw new HttpException( $ex->getMessage() );
		}
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'id' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}

	/**
	 * @param WorkflowId $id
	 * @return bool
	 * @throws Exception
	 */
	private function isValidId( WorkflowId $id ) {
		return true;
		// TODO: From ReadModel
	}
}
