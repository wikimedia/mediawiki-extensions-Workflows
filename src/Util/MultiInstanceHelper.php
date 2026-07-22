<?php

namespace MediaWiki\Extension\Workflows\Util;

use MediaWiki\Extension\Workflows\Definition\Element\Task;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\WorkflowContext;

class MultiInstanceHelper {

	/**
	 * @param ITask $task
	 * @param array &$prop
	 * @throws WorkflowExecutionException
	 */
	public function assertTarget( ITask $task, &$prop ) {
		if ( !$prop['target'] ) {
			if ( !isset( $task->getDataProperties()[$prop['source']] ) ) {
				throw new WorkflowExecutionException(
					'If no target is specified for multi-instance property, source must exist as a property',
					$task
				);
			}
			$prop['target'] = $prop['source'];
		}
	}

	/**
	 * @param ITask $task
	 * @param WorkflowContext $context
	 * @return array
	 * @throws WorkflowExecutionException
	 */
	public function getMultiInstancePropertyData( ITask $task, WorkflowContext $context ) {
		$multiProps = $task->getMultiInstanceCharacteristics()['props'];

		$sourceData = [];
		foreach ( $multiProps as $prop ) {
			$this->assertTarget( $task, $prop );
			if ( $this->isContextDataKey( $prop['source'] ) ) {
				$data = $this->getDataFromContext( $prop['source'], $context );
				if ( !$data ) {
					throw new WorkflowExecutionException(
						'Source data for multi-instance property not available',
						$task
					);
				}
			} else {
				$data = $this->getTaskLocalSourceValue( $task, $prop );
			}
			$sourceData[$prop['target']] = $data;
		}

		return $this->getDataSets( $sourceData );
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	private function isContextDataKey( $key ) {
		return strpos( $key, '.' ) !== false;
	}

	/**
	 * @param string $key
	 * @param WorkflowContext $context
	 * @return mixed|null
	 */
	private function getDataFromContext( $key, WorkflowContext $context ) {
		$bits = explode( '.', $key );
		$activity = array_shift( $bits );
		$dataKey = array_shift( $bits );
		return $context->getRunningData( $activity, $dataKey );
	}

	/**
	 * @param ITask $task
	 * @param array $prop
	 * @return array
	 */
	public function getTaskLocalSourceValue( ITask $task, $prop ) {
		if ( !isset( $task->getDataProperties()[$prop['source']] ) ) {
			throw new WorkflowExecutionException(
				'If source is declared as a local property, it must exist as a property',
				$task
			);
		}

		return explode( '|', $task->getDataProperties()[$prop['source']] );
	}

	/**
	 * @param array $data
	 * @return array
	 */
	private function getDataSets( $data ) {
		// Pad to same size
		$maxLength = 0;
		foreach ( $data as $key => $value ) {
			$size = count( $value );
			if ( $size > $maxLength ) {
				$maxLength = $size;
			}
		}
		foreach ( $data as $key => &$value ) {
			$size = count( $value );
			if ( $size < $maxLength ) {
				$value = array_pad( $value, $maxLength, $value[$size - 1] );
			}
		}
		$numberOfSets = count( array_values( $data )[0] );
		$sets = [];
		for ( $i = 0; $i < $numberOfSets; $i++ ) {
			$set = [];
			foreach ( $data as $key => &$value ) {
				$set[$key] = array_shift( $value );
			}
			$sets[] = $set;
		}

		return $sets;
	}

	/**
	 * @param ITask $task
	 * @param string $counter
	 * @return Task
	 */
	public function cloneTaskWithCounter( ITask $task, $counter ) {
		return new Task(
			$task->getId() . '_' . $counter,
			$task->getName(),
			$task->getIncoming(),
			$task->getOutgoing(),
			$task->getElementName(),
			$task->getDataProperties(),
			$task->getInputDataAssociations(),
			$task->getOutputDataAssociations(),
			$task->getExtensionElements(),
			$task->isLooping(),
			$task->getMultiInstanceCharacteristics()
		);
	}
}
