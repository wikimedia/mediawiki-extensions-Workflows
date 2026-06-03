<?php

namespace MediaWiki\Extension\Workflows\Tag;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\GenericTag;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;
use MWStake\MediaWiki\Component\GenericTagHandler\MarkerType;

class MyOpenWorkflows extends GenericTag {

	/**
	 * @inheritDoc
	 */
	public function getTagNames(): array {
		return [ 'myopenworkflows', 'bs:myopenworkflows' ];
	}

	/**
	 * @inheritDoc
	 */
	public function hasContent(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getMarkerType(): MarkerType {
		return new MarkerType\NoWiki();
	}

	/**
	 * @inheritDoc
	 */
	public function getContainerElementName(): ?string {
		return 'div';
	}

	/**
	 * @inheritDoc
	 */
	public function getHandler( MediaWikiServices $services ): ITagHandler {
		return new MyOpenWorkflowsHandler(
			$services->getService( 'WorkflowsStateStore' ),
			$services->getTitleFactory(),
			$services->getLinkRenderer()
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getParamDefinition(): ?array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getResourceLoaderModules(): ?array {
		return [ 'ext.workflows.droplet.myopenworkflows' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getClientTagSpecification(): ClientTagSpecification|null {
		return new ClientTagSpecification(
			'Myopenworkflows',
			Message::newFromKey( 'workflows-droplet-myopenworkflows-description' ),
			null,
			Message::newFromKey( 'workflows-droplet-myopenworkflows-name' )
		);
	}
}
