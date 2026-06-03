<?php

namespace MediaWiki\Extension\Workflows\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\TagDroplet;
use MediaWiki\Message\Message;

class MyOpenWorkflowsDroplet extends TagDroplet {

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return Message::newFromKey( 'workflows-droplet-myopenworkflows-name' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return Message::newFromKey( 'workflows-droplet-myopenworkflows-description' );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'droplet-myopenworkflows';
	}

	/**
	 * @return array
	 */
	public function getCategories(): array {
		return [ 'content', 'lists', 'data' ];
	}

	/**
	 * @return string
	 */
	protected function getTagName(): string {
		return 'bs:myopenworkflows';
	}

	/**
	 * @return array
	 */
	protected function getAttributes(): array {
		return [];
	}

	/**
	 * @return bool
	 */
	protected function hasContent(): bool {
		return false;
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [];
	}

	/**
	 * @return string|null
	 */
	public function getVeCommand(): ?string {
		return 'myopenworkflowsCommand';
	}
}
