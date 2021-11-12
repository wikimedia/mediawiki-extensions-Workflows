<?php

use MediaWiki\Extension\Workflows\Util\GroupDataProvider;
use MediaWiki\Extension\Workflows\Util\ThresholdCheckerFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\Workflows\Definition\Repository\DefinitionRepositoryFactory;
use MediaWiki\Extension\Workflows\Logger\GenericSpecialLogLogger;
use MediaWiki\Extension\Workflows\LogicObjectFactory;
use MediaWiki\Extension\Workflows\Query\Store\DBStateStore;
use MediaWiki\Extension\Workflows\Storage\MessageRepository\WorkflowMessageRepository;
use MediaWiki\Extension\Workflows\Util\DataPreprocessor;
use MediaWiki\Extension\Workflows\Storage\WorkflowEventRepository;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\Extension\Workflows\WorkflowSerializer;

return [
	'DefinitionRepositoryFactory' => function ( MediaWikiServices $services ) {
		$registry = ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsDefinitionRepositories' );
		return new DefinitionRepositoryFactory(
			$registry,
			$services
		);
	},

	'WorkflowEventRepository' => function ( MediaWikiServices $services ) {
		$messageRepository = WorkflowMessageRepository::newRepository(
			$services->getDBLoadBalancer()
		);

		$instance = new WorkflowEventRepository( $messageRepository );
		// Register state store with repo, so we get notified about events
		$stateStore = $services->getService( 'WorkflowsStateStore' );
		$instance->addConsumerToDispatcher( $stateStore );

		return $instance;
	},

	'WorkflowFactory' => function ( MediaWikiServices $services ) {
		return new WorkflowFactory(
			$services->getService( 'WorkflowEventRepository' ),
			$services->getService( 'DefinitionRepositoryFactory' )
		);
	},


	'WorkflowLogicObjectFactory' => function ( MediaWikiServices $services ) {
		return new LogicObjectFactory(
			ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsLogicObjects' ),
			$services->getService( 'WorkflowsSpecialLogLogger' ),
			$services->getService( 'WorkflowLogger' )
		);
	},

	'WorkflowsSpecialLogLogger' => function ( MediaWikiServices $services ) {
		return new GenericSpecialLogLogger();
	},

	'WorkflowLogger' => function ( MediaWikiServices $services ) {
		return LoggerFactory::getInstance( 'workflows' );
	},

	'WorkflowsDataPreprocessor' => function ( MediaWikiServices $services ) {
		$context = RequestContext::getMain();
		return new DataPreprocessor( $services->getParser(), $context );
	},

	'WorkflowUtilGroupDataProvider' => function( MediaWikiServices $services ) {
		return new GroupDataProvider();
	},

	'WorkflowUtilThresholdCheckerFactory' => function( MediaWikiServices $services ) {
		return new ThresholdCheckerFactory(
			$services->getService( 'WorkflowUtilGroupDataProvider' )
		);
	},

	'WorkflowsStateStore' => function ( MediaWikiServices $services ) {
		// For now, is hardcoded to DB store, but we might change this
		return new DBStateStore( $services->getDBLoadBalancer() );
	},

	'WorkflowSerializer' => function ( MediaWikiServices $services ) {
		return new WorkflowSerializer( $services->getService( 'WorkflowEventRepository' ) );
	},
];
