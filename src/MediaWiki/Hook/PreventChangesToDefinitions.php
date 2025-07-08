<?php

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Hook\MovePageIsValidMoveHook;
use MediaWiki\Title\Title;

class PreventChangesToDefinitions implements MovePageIsValidMoveHook {

	/**
	 * @param WorkflowStateStore $stateStore
	 */
	public function __construct(
		private readonly WorkflowStateStore $stateStore
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMovePageIsValidMove( $oldTitle, $newTitle, $status ) {
		if ( $oldTitle->getContentModel() !== 'BPMN' ) {
			return true;
		}
		if ( $newTitle->getContentModel() !== 'BPMN' ) {
			$status->setOK( false );
			$status->error( 'workflows-move-invalid-content-model' );
			return false;
		}
		if ( $this->isUsed( $oldTitle, true ) ) {
			$status->setOK( false );
			$status->error( 'workflows-move-definition-in-use' );
			return false;
		}
		if ( $this->isUsed( $oldTitle, false ) ) {
			$status->warning( 'workflows-move-definition-in-use-past' );
			return false;
		}
		return true;
	}

	/**
	 * @param Title $title
	 * @param bool $active
	 * @return bool
	 */
	private function isUsed( Title $title, bool $active ): bool {
		if ( $active ) {
			$this->stateStore->active();
		}
		$models = $this->stateStore->complexQuery( [
			'definition' => [
				'repositoryKey' => 'wikipage',
				'name' => substr( $title->getDBkey(), 0, -5 ),
			]
		] );
		return count( $models ) > 0;
	}
}
