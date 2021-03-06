<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Maintenance;

use CommentStoreComment;
use LoggedUpdateMaintenance;
use MediaWiki\Extension\Workflows\Definition\Repository\IDefinitionRepository;
use MediaWiki\Extension\Workflows\MediaWiki\Content\TriggerDefinitionContent;
use MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger;
use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MWException;
use Title;
use User;
use WikiPage;

class CreateDefaultTriggersPage extends LoggedUpdateMaintenance {
	/**
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		/** @var TriggerRepo $repo */
		$repo = MediaWikiServices::getInstance()->getService( 'WorkflowTriggerRepo' );
		$title = $repo->getTitle();

		if ( $title->exists() ) {
			$this->output( "...Page '{$title->getPrefixedDBkey()}' already exists\n" );
		} else {
			return $this->createPage( $title, $repo );
		}
		return true;
	}

	/**
	 * @param Title $title
	 * @param TriggerRepo $triggerRepo
	 * @throws MWException
	 * @return bool
	 */
	private function createPage( Title $title, TriggerRepo $triggerRepo ) {
		$this->output( "...Creating page '{$title->getPrefixedDBkey()}'..." );

		if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
			// MW 1.36+
			$wikipage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		} else {
			$wikipage = WikiPage::factory( $title );
		}
		$content = $this->getDefaultContent( $triggerRepo );
		$comment = CommentStoreComment::newUnsavedComment( 'Autogenerated' );
		$updater = $wikipage->newPageUpdater( User::newSystemUser( 'Mediawiki default' ) );
		$updater->setContent( SlotRecord::MAIN, $content );
		$newRevision = $updater->saveRevision( $comment );
		if ( $newRevision instanceof RevisionRecord ) {
			$this->output( "done.\n" );
		} else {
			$this->output( "error.\n" );
			return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'workflow-triggers-default-page';
	}

	/**
	 * @param TriggerRepo $triggerRepo
	 * @return TriggerDefinitionContent
	 */
	private function getDefaultContent( TriggerRepo $triggerRepo ): TriggerDefinitionContent {
		$repositoryFactory = MediaWikiServices::getInstance()->getService( 'DefinitionRepositoryFactory' );
		$triggers = [];
		foreach ( $repositoryFactory->getRepositoryKeys() as $key ) {
			/** @var IDefinitionRepository $repository */
			$repository = $repositoryFactory->getRepository( $key );
			$definitions = $repository->getAllKeys();
			foreach ( $definitions as $definition ) {
				$title = $repository->getDefinitionDisplayTitle( $definition );
				$triggerKey = $triggerRepo->generateTriggerKey( $title, 'manual' );
				$triggers[$triggerKey] = new PageRelatedTrigger(
					MediaWikiServices::getInstance()->getTitleFactory(),
					$triggerKey,
					$repository->getDefinitionDisplayTitle( $definition ),
					$repository->getDefinitionDescription( $definition ),
					'manual',
					$definition,
					$key,
					[], [], [],
					true
				);
			}
		}

		return new TriggerDefinitionContent( json_encode( $triggers ) );
	}
}
