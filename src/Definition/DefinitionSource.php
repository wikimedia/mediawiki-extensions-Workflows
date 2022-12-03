<?php

namespace MediaWiki\Extension\Workflows\Definition;

use JsonSerializable;
use Message;

class DefinitionSource implements JsonSerializable {
	/** @var string */
	private $repositoryKey;
	/** @var string */
	private $name;
	/** @var int */
	private $version;
	/** @var array */
	private $params;

	/**
	 * @param string $repositoryKey
	 * @param string $name
	 * @param int $version
	 * @param array $params
	 */
	public function __construct( $repositoryKey, $name, int $version, $params = [] ) {
		$this->repositoryKey = $repositoryKey;
		$this->name = $name;
		$this->version = $version;
		$this->params = $params;
	}

	/**
	 * @param array $data
	 * @return static
	 */
	public static function newFromArray( array $data ) {
		return new static(
			$data['repositoryKey'],
			$data['name'],
			$data['version'],
			$data['params']
		);
	}

	public function getRepositoryKey(): string {
		return $this->repositoryKey;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getVersion(): int {
		return $this->version;
	}

	public function getParams(): array {
		return $this->params;
	}

	public function getTitle(): string {
		return Message::newFromKey(
			"workflows-{$this->repositoryKey}-definition-{$this->name}-title"
		)->text();
	}

	public function getDescription(): string {
		return Message::newFromKey(
			"workflows-{$this->repositoryKey}-definition-{$this->name}-desc"
		)->text();
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'repositoryKey' => $this->repositoryKey,
			'name' => $this->name,
			'version' => $this->version,
			'params' => $this->params
		];
	}
}
