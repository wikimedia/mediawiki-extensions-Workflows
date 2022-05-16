<?php

namespace MediaWiki\Extension\Workflows\Trigger;

use MediaWiki\Extension\Workflows\IPageTrigger;
use MediaWiki\Extension\Workflows\UserInteractionModule;
use Title;

class PageRelatedTrigger extends GenericTrigger implements IPageTrigger {
	/** @var Title|null */
	protected $title = null;

	/**
	 * @param Title $title
	 */
	public function setTitle( Title $title ) {
		$this->title = $title;
	}

	protected function getContextData() {
		if ( !$this->title === null ) {
			return parent::getContextData();
		}
		return parent::getContextData() + [
			'pageId' => $this->title->getArticleID(),
			'revision' => $this->title->getLatestRevID()
		];
	}

	/**
	 * TODO: This part here needs rework (and the parts it calls)
	 * We need a better unified testing for rules
	 *
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool {
		if ( !$this->title ) {
			return false;
		}
		return $this->appliesToPage( $this->title, $qualifyingData );
	}

	/**
	 * @return UserInteractionModule|null
	 */
	public function getEditorModule(): ?UserInteractionModule {
		return new UserInteractionModule(
			'ext.workflows.trigger.editors',
			'workflows.object.form.trigger.PageRelated'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function isAutomatic(): bool {
		return false;
	}
}
