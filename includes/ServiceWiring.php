<?php

use MediaWiki\Extension\Workflows\ActivityManagerFactory;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Logger\GenericSpecialLogLogger;
use MediaWiki\Extension\Workflows\LogicObjectFactory;
use MediaWiki\Extension\Workflows\PropertyValidatorFactory;
use MediaWiki\Extension\Workflows\Query\Store\DBStateStore;
use MediaWiki\Extension\Workflows\Storage\MessageRepository\WorkflowMessageRepository;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\Util\DataPreprocessor;
use MediaWiki\Extension\Workflows\Util\GroupDataProvider;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Extension\Workflows\WorkflowSerializer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'DefinitionRepositoryFactory' => static function ( MediaWikiServices $services ) {
		$registry = ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsDefinitionRepositories' );
		return new DefinitionRepositoryFactory(
			$registry,
			$services->getObjectFactory()
		);
	},

	'WorkflowEventRepository' => static function ( MediaWikiServices $services ) {
		$messageRepository = WorkflowMessageRepository::newRepository(
			$services->getDBLoadBalancer()
		);

		$instance = new WorkflowEventRepository( $messageRepository );
		// Register state store with repo, so we get notified about events
		$stateStore = $services->getService( 'WorkflowsStateStore' );
		$instance->addConsumerToDispatcher( $stateStore );

		return $instance;
	},

	'WorkflowFactory' => static function ( MediaWikiServices $services ) {
		return new WorkflowFactory(
			$services->getService( 'WorkflowEventRepository' ),
			$services->getService( 'DefinitionRepositoryFactory' )
		);
	},

	'WorkflowLogicObjectFactory' => static function ( MediaWikiServices $services ) {
		return new LogicObjectFactory(
			ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsLogicObjects' ),
			$services->getService( 'WorkflowsSpecialLogLogger' ),
			$services->getService( 'WorkflowLogger' )
		);
	},

	'WorkflowsSpecialLogLogger' => static function ( MediaWikiServices $services ) {
		return new GenericSpecialLogLogger();
	},

	'WorkflowLogger' => static function ( MediaWikiServices $services ) {
		return LoggerFactory::getInstance( 'workflows' );
	},

	'WorkflowsDataPreprocessor' => static function ( MediaWikiServices $services ) {
		$context = RequestContext::getMain();
		return new DataPreprocessor( $services->getParser(), $context );
	},

	'WorkflowUtilGroupDataProvider' => static function ( MediaWikiServices $services ) {
		return new GroupDataProvider();
	},

	'WorkflowsStateStore' => static function ( MediaWikiServices $services ) {
		// For now, is hardcoded to DB store, but we might change this
		return new DBStateStore( $services->getDBLoadBalancer() );
	},

	'WorkflowSerializer' => static function ( MediaWikiServices $services ) {
		return new WorkflowSerializer( $services->getService( 'WorkflowEventRepository' ) );
	},

	'WorkflowTriggerRepo' => static function ( MediaWikiServices $services ) {
		$registry = ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsTriggerTypes' );

		return new \MediaWiki\Extension\Workflows\TriggerRepo(
			$services->getService( 'WorkflowFactory' ),
			$services->getTitleFactory(),
			$services->getService( 'WorkflowLogger' ),
			$services->getObjectFactory(),
			// Hardcoded pagename
			'MediaWiki:WorkflowTriggers',
			$registry
		);
	},
	'WorkflowTriggerRunner' => static function ( MediaWikiServices $services ) {
		return new \MediaWiki\Extension\Workflows\TriggerRunner(
			$services->getService( 'WorkflowTriggerRepo' ),
			$services->getService( 'WorkflowLogger' )
		);
	},

	'PropertyValidatorFactory' => static function ( MediaWikiServices $services ) {
		return new PropertyValidatorFactory(
			ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsPropertyValidators' ),
			$services->getObjectFactory()
		);
	},

	'WorkflowsActivityManagerFactory' => static function ( MediaWikiServices $services ) {
		return new ActivityManagerFactory(
			$services->getService( 'WorkflowLogicObjectFactory' ),
			$services->getService( 'WorkflowsDataPreprocessor' ),
			$services->getService( 'PropertyValidatorFactory' ),
			$services->getUserFactory()
		);
	},
];
