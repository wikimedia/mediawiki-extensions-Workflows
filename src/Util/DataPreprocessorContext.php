<?php

namespace MediaWiki\Extension\Workflows\Util;

use MediaWiki\Extension\Workflows\WorkflowContext;
use Title;
use User;

class DataPreprocessorContext {

	/** @var Title */
	private $title;
	/** @var User */
	private $user;
	/** @var bool|int|null */
	private $revisionId;

	/**
	 * @param WorkflowContext $context
	 * @return static
	 */
	public static function newFromWorkflowContext( WorkflowContext $context ) {
		$defContext = $context->getDefinitionContext();
		$pageId = $defContext->getItem( 'pageId' );
		$title = null;
		if ( $pageId ) {
			$title = Title::newFromID( $pageId );
		}

		return new static(
			$title, $context->getCurrentActor(), (int)$defContext->getItem( 'revision', 0 )
		);
	}

	/**
	 *
	 * @param Title|null $title
	 * @param User|null $user
	 * @param int|null $revisionId
	 */
	public function __construct( Title $title = null, User $user = null, int $revisionId = 0 ) {
		$this->title = $title;
		$this->user = $user;
		$this->revisionId = $revisionId;

		if ( $this->title === null ) {
			$this->title = Title::newMainPage();
		}
		if ( $this->revisionId === null ) {
			$this->revisionId = $this->title->getLatestRevID();
		}

		if ( $this->user === null ) {
			$this->user = User::newSystemUser( 'MediaWiki default' );
		}
	}

	/**
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 *
	 * @return User
	 */
	public function getUser() {
		return $this->user;
	}

	public function getRevisionId() {
		return $this->revisionId;
	}
}
