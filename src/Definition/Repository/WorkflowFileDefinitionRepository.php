<?php

namespace MediaWiki\Extension\Workflows\Definition\Repository;

use ExtensionRegistry;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;

class WorkflowFileDefinitionRepository extends FileRepository {
	/** @var array */
	private $attribute;
	/** @var string */
	private $fileBase;
	/** @var HookContainer */
	private $hookContainer;

	public static function factory( MediaWikiServices $services ) {
		return new static(
			ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsWorkflowDefinitions' ),
			$services->getMainConfig()->get( 'ExtensionDirectory' ),
			$services->getHookContainer()
		);
	}

	public function __construct( $attribute, $base, $hookContainer ) {
		$this->attribute = $attribute;
		$this->fileBase = $base;
		$this->hookContainer = $hookContainer;
	}

	protected function getFilePath( $file ) {
		if ( $this->isAbsoluteFilePath( $file ) ) {
			return $file;
		}
		return $this->getRootDirectory() . DIRECTORY_SEPARATOR . $file;
	}

	public function getName(): string {
		return 'workflow-file';
	}

	protected function load() {
		foreach ( $this->attribute as $key => $path ) {
			$this->register( $key, $path );
		}

		$this->hookContainer->run(
			'WorkflowFileDefinitionRepositoryLoad',
			[ $this ]
		);

		$this->loaded = true;
	}

	/**
	 * @return string
	 */
	protected function getRootDirectory() {
		return $this->fileBase;
	}

	private function isAbsoluteFilePath( $file ) {
		// TODO: This is pretty basic
		return file_exists( $file );
	}
}
