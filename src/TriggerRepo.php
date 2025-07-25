<?php

namespace MediaWiki\Extension\Workflows;

use InvalidArgumentException;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Content\Content;
use MediaWiki\Extension\Workflows\MediaWiki\Content\TriggerDefinitionContent;
use MediaWiki\Extension\Workflows\Query\WorkflowStateStore;
use MediaWiki\Json\FormatJson;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\PageUpdater;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikimedia\ObjectFactory\ObjectFactory;
use WikiPage;

class TriggerRepo {

	/** @var string */
	private $page;

	/** @var WorkflowFactory */
	private $workflowFactory;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var LoggerInterface */
	private $logger;

	/** @var ObjectFactory */
	private $objectFactory;

	/** @var WorkflowStateStore */
	private $workflowStore;

	/** @var bool */
	private $loaded = false;

	/** @var array */
	private $triggerTypeRegistry = [];

	/** @var ITrigger[] */
	private $triggers = [];

	/** @var WikiPage|null */
	private $wikipage = null;

	/** @var Content|null */
	private $content = null;

	/**
	 * @param WorkflowFactory $workflowFactory
	 * @param WorkflowStateStore $stateStore
	 * @param TitleFactory $titleFactory
	 * @param LoggerInterface $logger
	 * @param ObjectFactory $objectFactory
	 * @param string $page
	 * @param array $triggerTypeRegistry
	 */
	public function __construct(
		WorkflowFactory $workflowFactory, WorkflowStateStore $stateStore, TitleFactory $titleFactory,
		LoggerInterface $logger, ObjectFactory $objectFactory, $page, $triggerTypeRegistry
	) {
		$this->workflowFactory = $workflowFactory;
		$this->titleFactory = $titleFactory;
		$this->logger = $logger;
		$this->objectFactory = $objectFactory;
		$this->page = $page;
		$this->triggerTypeRegistry = $triggerTypeRegistry;
		$this->workflowStore = $stateStore;
	}

	/**
	 * @return array
	 */
	public function getAll(): array {
		$this->assertLoaded();

		return $this->triggers ?? [];
	}

	/**
	 * @param string $type
	 * @return ITrigger[]
	 */
	public function getAllOfType( $type ): array {
		$this->assertLoaded();
		$ofType = [];
		foreach ( $this->triggers as $key => $trigger ) {
			if ( $trigger->getType() === $type || $trigger->getNativeType() === $type ) {
				$ofType[] = $trigger;
			}
		}

		return $ofType;
	}

	/**
	 * @param string $type
	 * @return ITrigger[]
	 */
	public function getActive( $type ): array {
		$this->assertLoaded();
		return array_filter( $this->getAllOfType( $type ), static function ( $trigger ) {
			return $trigger->isActive();
		} );
	}

	private function assertLoaded() {
		if ( !$this->wikipage ) {
			$this->setWikipage();
		}
		if ( !$this->loaded ) {
			$this->load();
		}
	}

	/**
	 * @param Content|null $content
	 */
	private function load( $content = null ) {
		$this->triggers = [];
		// Avoid errors during initial install, when
		// `MediaWiki:WorkflowTriggers` does not exist yet
		if ( defined( 'MEDIAWIKI_INSTALL' ) || defined( 'MW_UPDATER' ) ) {
			$this->loaded = true;
			return;
		}
		if ( $content === null ) {
			$this->setWikipage();
			if ( $this->wikipage ) {
				$content = $this->wikipage->getContent();
				if ( !( $content instanceof TriggerDefinitionContent ) ) {
					$this->logger->error( 'Trigger page\'s content is not correct' );
					return;
				}

				if ( !$content->isValid() ) {
					$this->logger->error( 'Could not load trigger data from the page specified' );
					return;
				}
				$this->content = $content;
			}
		}

		if ( $this->content ) {
			$data = FormatJson::decode( $content->getText(), 1 );
			foreach ( $data as $key => $triggerData ) {
				$this->loadTriggerFromData( $key, $triggerData );
			}
		}

		$this->loaded = true;
	}

	/**
	 * @param string $name
	 * @param array $data
	 */
	private function loadTriggerFromData( $name, $data ) {
		$type = $data['type'] ?? null;
		if ( $type === null ) {
			$this->logger->error( 'No trigger type specified for trigger ' . $name );
			return;
		}

		if ( !isset( $this->triggerTypeRegistry[$type] ) ) {
			$this->logger->error( "Trigger type $type is not registered" );
			return;
		}

		$spec = $this->triggerTypeRegistry[$type];
		$spec['args'] = array_merge( $spec['args'] ?? [], [ $name, $data ] );

		$object = $this->objectFactory->createObject( $spec );
		if ( !( $object instanceof ITrigger ) ) {
			$this->logger->error(
				"Trigger must be of type " . ITrigger::class . "," . get_class( $object ) . " given"
			);
			return;
		}
		$object->setWorkflowFactory( $this->workflowFactory );
		if ( $object instanceof NoParallelTrigger ) {
			$object->setWorkflowStore( $this->workflowStore );
		}
		if ( $object instanceof LoggerAwareInterface ) {
			$object->setLogger( $this->logger );
		}

		$this->triggers[$object->getId()] = $object;
	}

	/**
	 * @param string $name
	 * @param UserIdentity $user
	 * @return bool
	 */
	public function deleteTrigger( $name, $user ) {
		$this->assertLoaded();
		$triggers = $this->getRawTriggers();
		if ( isset( $triggers[$name] ) ) {
			unset( $triggers[$name] );
			return $this->setContent( $triggers, $user );
		}

		return false;
	}

	/**
	 * Insert or update trigger
	 *
	 * @param string $name
	 * @param array $data
	 * @param User $user
	 * @return bool
	 */
	public function upsertTrigger( $name, $data, $user ) {
		$this->assertLoaded();
		$triggers = $this->getRawTriggers();
		if ( isset( $triggers[$name] ) ) {
			$triggers[$name] = array_merge( $triggers[$name], $data );
		} else {
			$triggers[$name] = $data;
		}

		return $this->setContent( $triggers, $user );
	}

	/**
	 * @param array $data
	 * @param UserIdentity $user
	 * @return bool
	 */
	public function setContent( $data, $user ) {
		$this->assertLoaded();
		$content = new TriggerDefinitionContent( FormatJson::encode( $data ) );
		$updater = $this->getPageUpdater( $user );
		$updater->setContent( SlotRecord::MAIN, $content );

		if ( $this->persistContent( $updater ) ) {
			$this->content = $content;
			$this->load( $content );
			return true;
		}

		return false;
	}

	/**
	 * Get trigger page title
	 * @return Title|null
	 */
	public function getTitle(): ?Title {
		$this->assertLoaded();
		if ( !$this->wikipage ) {
			return null;
		}
		return $this->wikipage->getTitle();
	}

	/**
	 * @param UserIdentity $user
	 * @return PageUpdater
	 */
	private function getPageUpdater( $user ) {
		return $this->wikipage->newPageUpdater( $user );
	}

	/**
	 * @return array
	 */
	private function getRawTriggers() {
		return FormatJson::decode( $this->content->getText(), 1 );
	}

	/**
	 * @param PageUpdater $updater
	 * @return bool
	 * @throws RuntimeException
	 */
	private function persistContent( PageUpdater $updater ) {
		$revision = $updater->saveRevision(
			CommentStoreComment::newUnsavedComment( 'Update' )
		);

		if ( !( $revision instanceof RevisionRecord ) ) {
			$status = $updater->getStatus();
			$first = count( $status->getErrors() ) > 0 ? $status->getErrors()[0] : null;

			// Do not consider a null-edit a failure
			return is_array( $first ) &&
				isset( $first['message'] ) &&
				$first['message'] === 'edit-no-change';
		}

		return true;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function setWikipage() {
		$title = $this->titleFactory->newFromText( $this->page );
		if ( !$title->exists() ) {
			$this->logger->error( 'Cannot load triggers from page ' . $this->page );
		}
		$this->wikipage = MediaWikiServices::getInstance()->getWikiPageFactory()
			->newFromTitle( $title );
	}

	/**
	 * Generate key for the trigger based on its name
	 * @param string $name
	 * @param string $type
	 * @return string
	 */
	public function generateTriggerKey( $name, $type ) {
		if ( !$name || !is_string( $name ) ) {
			$this->logger->error( 'Attempted to generate trigger key from invalid name', [
				'name' => $name
			] );
			throw new \UnexpectedValueException(
				'Cannot generate trigger key from invalid name: ' . $name
			);
		}
		$key = trim(
			str_replace( ' ', '-', strtolower( $name ) )
		);

		return "trigger-$key-$type";
	}
}
