<?php

namespace MediaWiki\Extension\Workflows;

use Exception;
use MediaWiki\Extension\Workflows\Activity\Activity;
use MediaWiki\Extension\Workflows\Activity\GenericActivity;
use MediaWiki\Extension\Workflows\Activity\UIActivity;
use MediaWiki\Extension\Workflows\Decision\DataBasedDecision;
use MediaWiki\Extension\Workflows\Definition\Element\Gateway;
use MediaWiki\Extension\Workflows\Definition\IElement;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\Logger\SpecialLogLoggerAwareInterface;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class LogicObjectFactory {
	/** @var array */
	private $registry;
	/** @var ISpecialLogLogger */
	private $specialLogLogger;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param array $registry
	 * @param ISpecialLogLogger $specialLogLogger
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		array $registry, ISpecialLogLogger $specialLogLogger, LoggerInterface $logger
	) {
		$this->registry = $registry;
		$this->specialLogLogger = $specialLogLogger;
		$this->logger = $logger;
	}

	/**
	 * Register a handler for the activity type
	 * Will override existing keys!
	 *
	 * @param callable $callable
	 * @param string $name
	 * @param string $type 'activity' or 'decision'
	 */
	public function register( $callable, $name, $type ) {
		$regKey = null;
		if ( $type === 'activity' ) {
			$regKey = 'ActivityRegistry';
		}
		if ( $type === 'decision' ) {
			$regKey = 'DecisionRegistry';
		}

		if ( !$regKey ) {
			return;
		}
		$this->registry[$regKey][$name] = $callable;
	}

	/**
	 * @param ITask $task
	 * @return IActivity
	 */
	public function getActivityForTask( ITask $task ): IActivity {
		return $this->getInstance( $task, 'ActivityRegistry' );
	}

	/**
	 * @param Gateway $gateway
	 * @return IDecision
	 */
	public function getDecisionForGateway( Gateway $gateway ): IDecision {
		return $this->getInstance( $gateway, 'DecisionRegistry' );
	}

	/**
	 * @param IElement $element
	 * @param string $registryKey
	 * @return mixed
	 * @throws Exception
	 */
	private function getInstance( IElement $element, $registryKey ) {
		$registry = $this->registry[$registryKey];
		$instance = $this->getObjectForElement( $element, $registry );
		if ( $instance instanceof IActivity ) {
			return $instance;
		} else {
			return $this->getGenericInstance( $element );
		}
	}

	/**
	 * @param IElement $element
	 * @return Activity|UIActivity|DataBasedDecision
	 * @throws Exception
	 */
	private function getGenericInstance( IElement $element ) {
		if ( $element instanceof ITask ) {
			if ( $element->getElementName() === 'userTask' ) {
				return new UIActivity( $element );
			}
			return new GenericActivity( $element );
		}

		if ( $element instanceof Gateway ) {
			return DataBasedDecision::factory( $element );
		}

		throw new Exception( 'Could not retrieve logic object for type ' . $element->getName() );
	}

	/**
	 * @param IElement $element
	 * @param array $registry
	 * @return IActivity|IDecision|null
	 */
	private function getObjectForElement( IElement $element, $registry ) {
		$extElements = $element->getExtensionElements();
		$spec = [];
		if (
			isset( $extElements['type'] ) && isset( $registry[$extElements['type']] )
		) {
			$specData = $registry[$extElements['type']];
			if ( is_callable( $specData ) ) {
				$spec = [
					'factory' => $specData,
					'args' => [ $element ]
				];
			} elseif (
				is_array( $specData ) &&
				( isset( $specData['class'] ) || isset( $specData['factory'] ) )
			) {
				$spec = array_merge( $specData, [
					'args' => [ $element ]
				] );
			}
		}

		return $this->getObjectFromSpec( $spec );
	}

	/**
	 * @param array $spec
	 * @return IActivity|IDecision|null
	 */
	private function getObjectFromSpec( $spec ) {
		try {
			// Must use OF from Services, to instantiate classes with "services" specified
			$instance = MediaWikiServices::getInstance()
				->getObjectFactory()->createObject( $spec );
		} catch ( Exception $ex ) {
			return null;
		}
		if ( $instance instanceof LoggerAwareInterface ) {
			$instance->setLogger( $this->logger );
		}
		if ( $instance instanceof SpecialLogLoggerAwareInterface ) {
			$instance->setSpecialLogLogger( $this->specialLogLogger );
		}

		/** This is kinda bad, if we had more LOs, we would probably need common interface, eg. ILogicObject */
		if ( $instance instanceof IActivity || $instance instanceof IDecision ) {
			return $instance;
		}

		return null;
	}
}
