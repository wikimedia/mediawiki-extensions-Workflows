<?php

namespace MediaWiki\Extension\Workflows\Definition\Repository;

use Exception;
use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Definition\Parser\BPMNDefinitionParser;
use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;
use MediaWiki\Revision\RevisionStore;
use Message;
use Title;
use Wikimedia\Rdbms\ILoadBalancer;

class WikiPageDefinitionRepository implements IDefinitionRepository {
	/** @var ILoadBalancer */
	private $lb;
	/** @var RevisionStore */
	private $revisionStore;
	/** @var array Page Titles containing definitions */
	private $pages = [];
	/** @var array Parsed IWorkflowObjects */
	private $workflows = [];
	/** @var bool */
	private $loaded = false;

	/**
	 * @param ILoadBalancer $lb
	 * @param RevisionStore $revisionStore
	 */
	public function __construct( ILoadBalancer $lb, RevisionStore $revisionStore ) {
		$this->lb = $lb;
		$this->revisionStore = $revisionStore;
	}

	public function getAllKeys(): array {
		if ( !$this->loaded ) {
			$this->load();
		}

		return array_keys( $this->pages );
	}

	/**
	 * @inheritDoc
	 */
	public function getDefinition( $definitionName, ?int $version = null ): WorkflowDefinition {
		if ( !isset( $this->workflows[$definitionName] ) ) {
			if ( !$this->loaded ) {
				$this->load();
			}
			if ( !isset( $this->pages[$definitionName] ) ) {
				throw new Exception( "Definition $definitionName not found!" );
			}
			$version = $version ?? $this->getLatestVersionForPage( $definitionName );
			$this->loadVersion( $definitionName, $version );
			if ( !isset( $this->pages[$definitionName]['versions'][$version] ) ) {
				throw new Exception( "Definition $definitionName not found in version $version!" );
			}
			$parser = new BPMNDefinitionParser( new DefinitionSource(
				$this->getName(),
				$definitionName,
				$version
			) );
			if ( !isset( $this->workflows[$definitionName] ) ) {
				$this->workflows[$definitionName] = [];
			}
			$this->workflows[$definitionName][$version] = $parser->parse(
				$this->pages[$definitionName]['versions'][$version]
			);
		}

		return $this->workflows[$definitionName][$version];
	}

	/**
	 * @param Title $title
	 * @param int $revId
	 * @return string|null
	 */
	protected function getDefinitionText( Title $title, $revId ): ?string {
		$revision = $this->revisionStore->getRevisionById( $revId );
		if ( $revision === null ) {
			return null;
		}
		$content = $revision->getContent( 'main' );
		if ( $content === null ) {
			return null;
		}
		if ( $content->getModel() === 'BPMN' ) {
			return $content->getText();
		}

		return null;
	}

	private function load() {
		$db = $this->lb->getConnection( DB_REPLICA );
		$rows = $db->select(
			'page',
			[ 'page_id', 'page_title', 'page_namespace' ],
			[ 'page_content_model' => 'BPMN' ],
			__METHOD__
		);

		foreach ( $rows as $row ) {
			$title = Title::newFromRow( $row );
			if ( $title instanceof Title ) {
				$this->pages[$this->stripExtension( $title->getDBkey() ) ] = [
					'title' => $title,
					'versions' => []
				];
			}
		}

		$this->loaded = true;
	}

	private function stripExtension( $text ) {
		if ( substr( $text, -5 ) !== '.bpmn' ) {
			return $text;
		}
		return substr( $text, 0, -5 );
	}

	/**
	 * Get latest revision of page
	 *
	 * @param string $definitionName
	 * @return int
	 */
	private function getLatestVersionForPage( string $definitionName ): int {
		/** @var Title $title */
		$title = $this->pages[$definitionName]['title'];
		return $title->getLatestRevID();
	}

	private function loadVersion( string $definitionName, int $version ) {
		$text = $this->getDefinitionText( $this->pages[$definitionName]['title'], $version );
		if ( $text === null ) {
			return;
		}
		$this->pages[$definitionName]['versions'][$version] = $text;
	}

	public function getName(): string {
		return 'wikipage';
	}

	public function getDefinitionDisplayTitle( $definition ): string {
		$message = Message::newFromKey( "workflows-wikipage-definition-$definition-title" );
		if ( $message->exists() ) {
			return $message->text();
		}
		return $definition;
	}

	public function getDefinitionDescription( $definition ): string {
		$message = Message::newFromKey( "workflows-wikipage-definition-$definition-desc" );
		if ( $message->exists() ) {
			return $message->text();
		}
		return '';
	}
}
