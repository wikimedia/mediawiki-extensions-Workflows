<?php

namespace MediaWiki\Extension\Workflows\Activity;

use DateTime;
use MediaWiki\Extension\Forms\DefinitionManager;
use MediaWiki\Extension\Workflows\ActivityDescriptor\GenericDescriptor;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;

class UIActivity extends GenericActivity implements UserInteractiveActivity {
	/** @var DateTime|null */
	protected $dueDate = null;

	/**
	 * @return UserInteractionModule
	 */
	public function getUserInteractionModule(): UserInteractionModule {
		$definitionForm = $this->getDefinitionFormModule();
		if ( $definitionForm instanceof UserInteractionModule ) {
			return $definitionForm;
		}
		$localForm = $this->getLocalFormModule();
		if ( $localForm instanceof UserInteractionModule ) {
			return $localForm;
		}

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
			return null;
		}

		$definition = $this->getDefinitionJSON( $form );
		if ( !$definition ) {
			return null;
		}

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
			return null;
		}

		$module = $moduleData['module'] ?? null;
		$class = $moduleData['class'] ?? null;
		$callback = $moduleData['callback'] ?? null;
		if ( $module === null || ( $class === null && $callback === null ) ) {
			return null;
		}

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
			return $definitionManager->getDefinition( $form );
		} catch ( \MWException $ex ) {
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
		return new GenericDescriptor( $this );
	}
}
