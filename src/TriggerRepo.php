<?php

namespace MediaWiki\Extension\Workflows;

use CommentStoreComment;
use Content;
use FormatJson;
use JsonContent;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\PageUpdater;
use MediaWiki\Storage\SlotRecord;
use MWException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use TitleFactory;
use Wikimedia\ObjectFactory;
use WikiPage;

class TriggerRepo {
	public const GROUP_BY_KEY = 'key';
	public const GROUP_BY_TYPE = 'type';

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
	 * @param \TitleFactory $titleFactory
	 * @param LoggerInterface $logger
	 * @param ObjectFactory $objectFactory
	 * @param string $page
	 * @param array $triggerTypeRegistry
	 */
	public function __construct(
		WorkflowFactory $workflowFactory, \TitleFactory $titleFactory, LoggerInterface $logger,
		ObjectFactory $objectFactory, $page, $triggerTypeRegistry
	) {
		$this->workflowFactory = $workflowFactory;
		$this->titleFactory = $titleFactory;
		$this->logger = $logger;
		$this->objectFactory = $objectFactory;
		$this->page = $page;
		$this->triggerTypeRegistry = $triggerTypeRegistry;

		$this->triggers = [
			static::GROUP_BY_KEY => [],
			static::GROUP_BY_TYPE => []
		];
	}

	/**
	 * @param string $groupBy
	 * @return array
	 */
	public function getAll( $groupBy = self::GROUP_BY_TYPE ): array {
		$this->assertLoaded();
		return $this->triggers[$groupBy];
	}

	/**
	 * @param string $type
	 * @return ITrigger[]
	 */
	public function getAllOfType( $type ): array {
		$this->assertLoaded();
		if ( !isset( $this->triggers[static::GROUP_BY_TYPE][$type] ) ) {
			return [];
		}
		return $this->triggers[static::GROUP_BY_TYPE][$type];
	}

	/**
	 * @param string $type
	 * @return ITrigger[]
	 */
	public function getActive( $type ): array {
		$this->assertLoaded();
		return array_filter( $this->getAllOfType( $type ),  static function ( $trigger ) {
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
		if ( $content === null ) {
			$this->setWikipage();
			$content = $this->wikipage->getContent();
			if ( !( $content instanceof JsonContent ) ) {
				$this->logger->error( 'Trigger page\'s content is not JSON' );
				return;
			}

			if ( !$content->isValid() ) {
				$this->logger->error( 'Could not load trigger data from the page specified' );
				return;
			}
			$this->content = $content;
		}

		$data = FormatJson::decode( $content->getText(), 1 );
		foreach ( $data as $key => $triggerData ) {
			$this->loadTriggerFromData( $key, $triggerData );
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
		if ( $object instanceof LoggerAwareInterface ) {
			$object->setLogger( $this->logger );
		}

		if ( !isset( $this->triggers[static::GROUP_BY_TYPE][$type] ) ) {
			$this->triggers[static::GROUP_BY_TYPE][$type] = [];
		}
		$this->triggers[static::GROUP_BY_TYPE][$type][] = $object;

		$this->triggers[static::GROUP_BY_KEY][$object->getName()] = $object;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function deleteTrigger( $name ) {
		$this->assertLoaded();
		$triggers = $this->getRawTriggers();
		if ( isset( $triggers[$name] ) ) {
			unset( $triggers[$name] );
			return $this->setContent( $triggers );
		}

		return false;
	}

	/**
	 * Insert or update trigger
	 *
	 * @param string $name
	 * @param array $data
	 * @return bool
	 */
	public function upsertTrigger( $name, $data ) {
		$this->assertLoaded();
		$triggers = $this->getRawTriggers();
		if ( isset( $triggers[$name] ) ) {
			$triggers[$name] = array_merge( $triggers[$name], $data );
		} else {
			$triggers[$name] = $data;
		}

		return $this->setContent( $triggers );
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function setContent( $data ) {
		$this->assertLoaded();
		$content = new JsonContent( FormatJson::encode( $data ) );
		$updater = $this->getPageUpdater();
		$updater->setContent( SlotRecord::MAIN, $content );

		if ( $this->persistContent( $updater ) ) {
			$this->content = $content;
			$this->load( $content );
			return true;
		}

		return false;
	}

	private function getPageUpdater() {
		return $this->wikipage->newPageUpdater( \User::newSystemUser( 'MediaWiki default' ) );
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
	 * @throws MWException
	 */
	private function persistContent( PageUpdater $updater ) {
		return $updater->saveRevision(
				CommentStoreComment::newUnsavedComment( 'Update' )
			) instanceof RevisionRecord;
	}

	/**
	 * @throws MWException
	 */
	private function setWikipage() {
		$title = $this->titleFactory->newFromText( $this->page );
		if ( !$title->exists() ) {
			$this->logger->error( 'Cannot load triggers from page ' . $this->page );
		}

		// No WikiPageFactory service yet :(
		$this->wikipage = WikiPage::factory( $title );
	}
}
