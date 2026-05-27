<?php

namespace MediaWiki\Extension\Workflows;

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
use Wikimedia\ObjectCache\WANObjectCache;
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

	/** @var WANObjectCache */
	private $cache;

	/**
	 * @param WorkflowFactory $workflowFactory
	 * @param WorkflowStateStore $stateStore
	 * @param TitleFactory $titleFactory
	 * @param LoggerInterface $logger
	 * @param ObjectFactory $objectFactory
	 * @param string $page
	 * @param array $triggerTypeRegistry
	 * @param WANObjectCache $cache
	 */
	public function __construct(
		WorkflowFactory $workflowFactory, WorkflowStateStore $stateStore, TitleFactory $titleFactory,
		LoggerInterface $logger, ObjectFactory $objectFactory, $page, $triggerTypeRegistry,
		WANObjectCache $cache
	) {
		$this->workflowFactory = $workflowFactory;
		$this->titleFactory = $titleFactory;
		$this->logger = $logger;
		$this->objectFactory = $objectFactory;
		$this->page = $page;
		$this->triggerTypeRegistry = $triggerTypeRegistry;
		$this->workflowStore = $stateStore;
		$this->cache = $cache;
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
		if ( $this->loaded ) {
			return;
		}
		if ( !$this->wikipage ) {
			$this->setWikipage();
		}
		$this->load();
	}

	/**
	 * @param Content|null $content
	 */
	private function load( ?Content $content = null ) {
		$this->triggers = [];
		// Avoid errors during initial install, when
		// `MediaWiki:WorkflowTriggers` does not exist yet
		if ( defined( 'MEDIAWIKI_INSTALL' ) || defined( 'MW_UPDATER' ) ) {
			$this->loaded = true;
			return;
		}

		if ( $content !== null ) {
			if ( $content instanceof TriggerDefinitionContent && $content->isValid() ) {
				$this->content = $content;
				$data = FormatJson::decode( $content->getText(), true );
				if ( is_array( $data ) ) {
					foreach ( $data as $key => $triggerData ) {
						$this->loadTriggerFromData( $key, $triggerData );
					}
				}
			}
			$this->loaded = true;
			return;
		}

		$data = $this->getCachedTriggerData();
		if ( $data === null ) {
			return;
		}
		foreach ( $data as $key => $triggerData ) {
			$this->loadTriggerFromData( $key, $triggerData );
		}
		$this->loaded = true;
	}

	/**
	 * Return decoded trigger data from cache, or null when the source page is
	 * missing / invalid (so the caller can retry on the next request).
	 * Invalidate cache on lookup fail.
	 *
	 * @return array|null
	 */
	private function getCachedTriggerData(): ?array {
		$result = $this->cache->getWithSetCallback(
			$this->cache->makeKey( 'workflows-triggers', $this->page ),
			WANObjectCache::TTL_DAY,
			function ( $oldValue, &$ttl ) {
				$data = $this->readTriggerDataFromPage();
				if ( $data === null ) {
					$ttl = WANObjectCache::TTL_UNCACHEABLE;
				}
				return $data;
			},
			[ 'checkKeys' => [ $this->makeCheckKey() ] ]
		);
		return is_array( $result ) ? $result : null;
	}

	/**
	 * Read the raw trigger data array directly from the wiki page.
	 * Returns null when the page is absent or its content is invalid.
	 *
	 * @return array|null
	 */
	private function readTriggerDataFromPage(): ?array {
		if ( !$this->wikipage ) {
			$this->setWikipage();
		}
		if ( !$this->wikipage ) {
			return null;
		}
		$content = $this->wikipage->getContent();
		if ( !( $content instanceof TriggerDefinitionContent ) ) {
			if ( $content !== null ) {
				$this->logger->error( 'Trigger page\'s content is not correct' );
			}
			return null;
		}
		if ( !$content->isValid() ) {
			$this->logger->error( 'Could not load trigger data from the page specified' );
			return null;
		}
		return FormatJson::decode( $content->getText(), true ) ?? [];
	}

	/**
	 * Invalidate the trigger cache. Call this whenever MediaWiki:WorkflowTriggers is saved.
	 */
	public function invalidateCache(): void {
		$this->cache->touchCheckKey( $this->makeCheckKey() );
	}

	/**
	 * @return string
	 */
	private function makeCheckKey(): string {
		return $this->cache->makeKey( 'workflows-triggers-check', $this->page );
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
		$this->setWikipage( mustExist: false );
		return $this->wikipage?->getTitle();
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
	private function getRawTriggers(): array {
		if ( !$this->wikipage ) {
			$this->setWikipage();
		}
		if ( $this->wikipage ) {
			$content = $this->wikipage->getContent();
			if ( $content instanceof TriggerDefinitionContent ) {
				$this->content = $content;
			}
		}
		if ( !$this->content ) {
			return [];
		}
		return FormatJson::decode( $this->content->getText(), true ) ?? [];
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
	 * @param bool $mustExist
	 */
	private function setWikipage( bool $mustExist = true ) {
		$title = $this->titleFactory->newFromText( $this->page );
		if ( !$title ) {
			return;
		}
		if ( $mustExist && !$title->exists() ) {
			$this->logger->warning( 'Cannot load triggers from page ' . $this->page . ' - page does not exist' );
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
