<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Maintenance;

use CommentStoreComment;
use Exception;
use MediaWiki\Extension\Workflows\Definition\Repository\FileRepository;
use MediaWiki\Extension\Workflows\Definition\Repository\IDefinitionRepository;
use MediaWiki\Extension\Workflows\MediaWiki\Content\TriggerDefinitionContent;
use MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger;
use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MWException;

require_once dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/maintenance/Maintenance.php';

class CreateDefaultTriggersPage extends LoggedUpdateMaintenance {

	/** @var MediaWikiServices */
	protected $services = null;

	public function __construct() {
		parent::__construct();
		$this->services = MediaWikiServices::getInstance();
	}

	/**
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		/** @var TriggerRepo $repo */
		$repo = $this->services->getService( 'WorkflowTriggerRepo' );
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

		$wikipage = $this->services->getWikiPageFactory()->newFromTitle( $title );
		$content = $this->getDefaultContent( $triggerRepo );
		$comment = CommentStoreComment::newUnsavedComment( 'Autogenerated' );
		$updater = $wikipage->newPageUpdater( User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] ) );
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
		$repositoryFactory = $this->services->getService( 'DefinitionRepositoryFactory' );
		$triggers = [];
		$titleFactory = $this->services->getTitleFactory();
		foreach ( $repositoryFactory->getRepositoryKeys() as $key ) {
			/** @var IDefinitionRepository $repository */
			$repository = $repositoryFactory->getRepository( $key );
			$definitions = $repository->getAllKeys();
			foreach ( $definitions as $definition ) {
				$name = $this->getTriggerName( $repository, $definition );
				$description = $this->getTriggerDescription( $repository, $definition );
				$title = $repository->getDefinitionDisplayTitle( $definition );
				$triggerKey = $triggerRepo->generateTriggerKey( $title, 'manual' );
				$trigger = new PageRelatedTrigger(
					$titleFactory,
					$triggerKey,
					$name,
					$description,
					'manual',
					$definition,
					$key,
					[], [], [],
					true
				);
				$triggerData = $trigger->jsonSerialize();
				// We do not want this in the default content
				unset( $triggerData['name_parsed'] );
				unset( $triggerData['description_parsed'] );
				$triggers[$triggerKey] = $triggerData;
			}
		}

		return new TriggerDefinitionContent( json_encode( $triggers ) );
	}

	/**
	 * @param IDefinitionRepository $repository
	 * @param string $definition
	 *
	 * @return string
	 * @throws Exception
	 */
	private function getTriggerName( IDefinitionRepository $repository, $definition ): string {
		if ( !( $repository instanceof FileRepository ) ) {
			return $repository->getDefinitionDisplayTitle( $definition );
		}
		$msg = Message::newFromKey( "workflows-workflow-file-definition-$definition-title" );
		if ( $msg->exists() ) {
			return $msg->getKey();
		}

		return $repository->getDefinitionDisplayTitle( $definition );
	}

	/**
	 * @param IDefinitionRepository $repository
	 * @param string $definition
	 *
	 * @return string
	 * @throws Exception
	 */
	private function getTriggerDescription( IDefinitionRepository $repository, $definition ): string {
		if ( !( $repository instanceof FileRepository ) ) {
			return $repository->getDefinitionDescription( $definition );
		}
		$msg = Message::newFromKey( "workflows-workflow-file-definition-$definition-desc" );
		if ( $msg->exists() ) {
			return $msg->getKey();
		}

		return $repository->getDefinitionDescription( $definition );
	}
}

$maintClass = CreateDefaultTriggersPage::class;
require_once RUN_MAINTENANCE_IF_MAIN;
