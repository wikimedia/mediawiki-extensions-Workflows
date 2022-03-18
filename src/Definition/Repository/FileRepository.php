<?php

namespace MediaWiki\Extension\Workflows\Definition\Repository;

use Exception;
use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use MediaWiki\Extension\Workflows\Definition\Parser\BPMNDefinitionParser;
use MediaWiki\Extension\Workflows\Definition\WorkflowDefinition;
use Message;
use MWException;

abstract class FileRepository implements IDefinitionRepository {
	/** @var array */
	protected $files = [];
	/** @var array Parsed IWorkflowObjects */
	protected $workflows = [];
	/** @var bool */
	protected $loaded = false;

	public function getAllKeys(): array {
		if ( !$this->loaded ) {
			$this->load();
		}

		return array_keys( $this->files );
	}

	/**
	 * @inheritDoc
	 */
	public function getDefinition( $definitionName, ?int $version = null ): WorkflowDefinition {
		if ( !isset( $this->workflows[$definitionName] ) ) {
			if ( !$this->loaded ) {
				$this->load();
			}
			if ( !isset( $this->files[$definitionName] ) ) {
				throw new Exception( "Definition $definitionName not found!" );
			}

			$parser = new BPMNDefinitionParser( new DefinitionSource(
				$this->getName(),
				$definitionName,
				1
			) );
			$this->workflows[$definitionName] = $parser->parse(
				$this->files[$definitionName]
			);
		}

		return $this->workflows[$definitionName];
	}

	/**
	 * Will try to get message
	 * workflows-file-repository-{definition_key}-title
	 *
	 * @param string $definition
	 * @return string
	 * @throws Exception
	 */
	public function getDefinitionDisplayTitle( $definition ): string {
		return $this->getDefinitionDisplayText( $definition );
	}

	/**
	 * Will try to get message
	 * workflows-file-repository-{definition_key}-desc
	 *
	 * @param string $definition
	 * @return string
	 * @throws Exception
	 */
	public function getDefinitionDescription( $definition ): string {
		return $this->getDefinitionDisplayText( $definition, 'desc' );
	}

	/**
	 * @param string $key
	 * @param string $filePath
	 * @throws MWException
	 */
	public function register( $key, $filePath ) {
		$fullPath = $this->getFilePath( $filePath );
		if ( !file_exists( $fullPath ) ) {
			throw new MWException( 'Cannot find definition file at ' . $fullPath );
		}
		$this->files[$key] = $this->getDefinitionText( $filePath );
	}

	/**
	 * @param string $key
	 */
	public function unregister( $key ) {
		if ( isset( $this->files[$key] ) ) {
			unset( $this->files[$key] );
		}
		if ( isset( $this->workflows[$key] ) ) {
			unset( $this->workflows[$key] );
		}
	}

	/**
	 * @param string $definition
	 * @param string $type
	 * @return string
	 * @throws Exception
	 */
	protected function getDefinitionDisplayText( $definition, $type = 'title' ) {
		$this->getDefinition( $definition );
		$message = Message::newFromKey( "workflows-workflow-file-definition-$definition-$type" );
		if ( $message->exists() ) {
			return $message->text();
		}
		return $type === 'title' ? $definition : '';
	}

	protected function getDefinitionText( $file ): ?string {
		$res = file_get_contents( $this->getFilePath( $file ) );
		if ( $res ) {
			return $res;
		}

		return null;
	}

	abstract protected function getFilePath( $file );

	abstract protected function getRootDirectory();

	protected function load() {
		$files = preg_grep( '~\.(bpmn)$~', scandir( $this->getRootDirectory() ) );
		foreach ( $files as $file ) {
			$this->files[$this->stripExtension( $file )] = $this->getDefinitionText( $file );
		}

		$this->loaded = true;
	}

	private function stripExtension( $text ) {
		if ( substr( $text, -5 ) !== '.bpmn' ) {
			return $text;
		}
		return substr( $text, 0, -5 );
	}
}
