<?php

namespace MediaWiki\Extension\Workflows\Rest;

use MediaWiki\Rest\Handler;

class RetrieveTriggerTypesHandler extends Handler {
	/**
	 * @inheritDoc
	 */
	public function execute() {
		$typesAttribute = \ExtensionRegistry::getInstance()->getAttribute(
			'WorkflowsTriggerTypes'
		);
		$editors = \ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsTriggerEditors' );

		$types = [];
		foreach ( $typesAttribute as $key => $class ) {
			$labelMessage = \Message::newFromKey( 'workflows-triggers-type-' . $key . '-label' );
			if ( $labelMessage->exists() ) {
				$label = $labelMessage->text();
			} else {
				$label = $key;
			}
			$descMessage = \Message::newFromKey( 'workflows-triggers-type-' . $key . '-desc' );
			$desc = '';
			if ( $descMessage->exists() ) {
				$desc = $descMessage->text();
			}
			$typeEditor = null;
			foreach ( $editors as $editorKey => $data ) {
				$supports = $data['supports'] ?? null;
				if ( is_array( $supports ) && in_array( $key, $supports ) ) {
					$typeEditor = [
						'module' => $data['module'],
						'class' => $data['class'] ?? '',
						'callback' => $data['callback'] ?? '',
					];
					break;
				}
			}
			$types[$key] = [
				'label' => $label,
				'desc' => $desc,
				'editor' => $typeEditor
			];
		}
		return $this->getResponseFactory()->createJson( $types );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [];
	}
}
