<?php

namespace MediaWiki\Extension\Workflows;

use CommentStoreComment;
use Content;
use FormatJson;
use MediaWiki\Extension\Workflows\MediaWiki\Content\TriggerDefinitionContent;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\PageUpdater;
use MWException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Title;
use TitleFactory;
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
		if ( $object instanceof LoggerAwareInterface ) {
			$object->setLogger( $this->logger );
		}

		$this->triggers[$object->getId()] = $object;
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
		$content = new TriggerDefinitionContent( FormatJson::encode( $data ) );
		$updater = $this->getPageUpdater();
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
	 * @throws MWException
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
