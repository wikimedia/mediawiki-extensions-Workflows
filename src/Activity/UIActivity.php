<?php

namespace MediaWiki\Extension\Workflows\Activity;

use DateTime;
use MediaWiki\Extension\Forms\DefinitionManager;
use MediaWiki\Extension\Workflows\ActivityDescriptor\GenericDescriptor;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MWException;

class UIActivity extends GenericActivity implements UserInteractiveActivity {
	/** @var DateTime|null */
	protected $dueDate = null;

	/**
	 * @return UserInteractionModule
	 */
	public function getUserInteractionModule(): UserInteractionModule {
		$this->logger->debug( '{method} called for {taskname}', [
			'method' => __METHOD__,
			'taskname' => $this->getTask()->getName()
		] );
		$definitionForm = $this->getDefinitionFormModule();
		if ( $definitionForm instanceof UserInteractionModule ) {
			$this->logger->debug( 'Using `DefinitionForm`: {json}', [
				'json' => $definitionForm->jsonSerialize()
			] );
			return $definitionForm;
		}
		$localForm = $this->getLocalFormModule();
		if ( $localForm instanceof UserInteractionModule ) {
			$this->logger->debug( 'Using `LocalForm`: {json}', [
				'json' => $localForm->jsonSerialize()
			] );
			return $localForm;
		}

		$this->logger->debug( 'Using `GenericForm`' );
		return UserInteractionModule::newGeneric();
	}

	/**
	 * Get UI Module for the form defined in definition
	 *
	 * @return UserInteractionModule|null
	 */
	protected function getDefinitionFormModule(): ?UserInteractionModule {
		$form = $this->getExtensionElementData( 'form' );
		if ( !$form ) {
			$this->logger->debug( 'No `form` element in definition found' );
			return null;
		}

		$definition = $this->getDefinitionJSON( $form );
		if ( !$definition ) {
			$this->logger->debug( 'Could not get definition JSON from `form` element data' );
			return null;
		}

		$this->logger->debug( 'Loaded definition JSON from `form` element data' );
		return UserInteractionModule::newFromDefinitionForm( $definition );
	}

	/**
	 * Get UI Module for the form defined in code
	 *
	 * @return UserInteractionModule|null
	 */
	protected function getLocalFormModule(): ?UserInteractionModule {
		$moduleData = $this->getExtensionElementData( 'formModule' );
		if ( !$moduleData ) {
			$this->logger->debug( 'No `formModule` element in definition found' );
			return null;
		}

		$module = $moduleData['module'] ?? null;
		$class = $moduleData['class'] ?? null;
		$callback = $moduleData['callback'] ?? null;
		if ( $module === null || ( $class === null && $callback === null ) ) {
			$this->logger->debug(
				'Couldn not find any of `module|class|callback` in `formModule` element'
			);
			return null;
		}

		$this->logger->debug( 'Loaded definition JSON from `formModule` element data' );
		return new UserInteractionModule( $module, $class, $callback );
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	protected function getExtensionElementData( $name ) {
		return isset( $this->getTask()->getExtensionElements()[$name] ) ?
			$this->getTask()->getExtensionElements()[$name] : null;
	}

	/**
	 * @inheritDoc
	 */
	public function isInitializer(): bool {
		$extElements = $this->getTask()->getExtensionElements();

		return isset( $extElements['initializer'] ) &&
		$extElements['initializer'] !== 'false' && $extElements['initializer'] !== '0';
	}

	/**
	 * @inheritDoc
	 */
	private function getDefinitionJSON( $form ) {
		// Hard dependency to Forms extension
		$definitionManager = new DefinitionManager();
		try {
			$this->logger->debug( 'Trying to get definition for `{form}`', [
				'form' => $form
			] );
			return $definitionManager->getDefinition( $form );
		} catch ( MWException $ex ) {
			$this->logger->debug( 'Failed: {message}', [
				'message' => $ex->getMessage()
			] );
			return null;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getTargetUsers( array $properties ): ?array {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function setDueDate( DateTime $date ) {
		$this->dueDate = $date;
	}

	/**
	 * @inheritDoc
	 */
	public function getDueDate(): ?DateTime {
		return $this->dueDate;
	}

	/**
	 * @inheritDoc
	 */
	public function getActivityDescriptor(): IActivityDescriptor {
		return new GenericDescriptor( $this, $this->logger );
	}
}
