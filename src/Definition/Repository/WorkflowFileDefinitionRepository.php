<?php

namespace MediaWiki\Extension\Workflows\Definition\Repository;

use Config;
use ExtensionRegistry;
use MediaWiki\HookContainer\HookContainer;

class WorkflowFileDefinitionRepository extends FileRepository {
	/** @var array */
	private $attribute;
	/** @var string */
	private $fileBase;
	/** @var HookContainer */
	private $hookContainer;

	public static function factory( Config $config, HookContainer $hookContainer ) {
		return new static(
			ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsWorkflowDefinitions' ),
			$config->get( 'ExtensionDirectory' ),
			$hookContainer
		);
	}

	public function __construct( $attribute, $base, $hookContainer ) {
		$this->attribute = $attribute;
		$this->fileBase = $base;
		$this->hookContainer = $hookContainer;
	}

	protected function getFilePath( $file ) {
		if ( is_array( $file ) ) {
			$file = array_pop( $file );
		}
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
