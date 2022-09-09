<?php

namespace MediaWiki\Extension\Workflows\Activity;

use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\User\UserFactory;
use Message;
use MWContentSerializationException;
use MWException;
use MWStake\MediaWiki\Component\Wikitext\Node\Transclusion;
use MWStake\MediaWiki\Component\Wikitext\ParserFactory;
use Title;
use TitleFactory;
use User;

class SetTemplateParamsActivity extends GenericActivity {
	/** @var ParserFactory */
	private $parserFactory;
	/** @var TitleFactory */
	private $titleFactory;
	/** @var RevisionStore */
	private $revisionStore;
	/** @var UserFactory */
	private $userFactory;
	/** @var PermissionManager */
	private $permissionManager;

	/** @var Title */
	private $title;
	/** @var User */
	private $user;
	/** @var int */
	private $templateIndex;
	/** @var int|string */
	private $templateParamIndex;
	/** @var string */
	private $value;
	/** @var bool */
	private $isMinor;
	/** @var string */
	private $comment;

	/**
	 * @param ParserFactory $parserFactory
	 * @param TitleFactory $titleFactory
	 * @param RevisionStore $revisionStore
	 * @param UserFactory $userFactory
	 * @param PermissionManager $permissionManager
	 * @param ITask $task
	 */
	public function __construct(
		ParserFactory $parserFactory, TitleFactory $titleFactory, RevisionStore $revisionStore,
		UserFactory $userFactory, PermissionManager $permissionManager, ITask $task
	) {
		parent::__construct( $task );
		$this->parserFactory = $parserFactory;
		$this->titleFactory = $titleFactory;
		$this->revisionStore = $revisionStore;
		$this->userFactory = $userFactory;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @param array $data
	 * @param WorkflowContext $context
	 * @return ExecutionStatus
	 * @throws WorkflowExecutionException
	 * @throws MWContentSerializationException
	 * @throws MWException
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$this->assertData( $data );

		$revision = $this->revisionStore->getRevisionByTitle( $this->title );
		if ( !( $revision instanceof RevisionRecord ) ) {
			throw new WorkflowExecutionException(
				Message::newFromKey( 'workflows-activity-error-no-revision' )->text(),
				$this->getTask()
			);
		}
		$parser = $this->parserFactory->newRevisionParser( $revision );
		$templates = $parser->parse();

		if ( empty( $templates ) || !isset( $templates[$this->templateIndex] ) ) {
			throw new WorkflowExecutionException(
				Message::newFromKey( 'workflows-activity-set-template-params-no-target' )->text()
			);
		}

		/** @var Transclusion $node */
		$node = $templates[$this->templateIndex];
		$node->setParam( $this->templateParamIndex, $this->value );

		$parser->replaceNode( $node );
		$rev = $parser->saveRevision( $this->user, $this->comment, $this->isMinor ? EDIT_MINOR : 0 );
		if ( !( $rev instanceof RevisionRecord ) ) {
			throw new WorkflowExecutionException(
				Message::newFromKey( 'workflows-activity-set-template-params-cannot-save' )->text()
			);
		}
		return new ExecutionStatus( Activity::STATUS_COMPLETE, [
			'revisionId' => $rev->getId(),
			'timestamp' => $rev->getTimestamp(),
		] );
	}

	/**
	 * @param array $data
	 * @throws WorkflowExecutionException
	 */
	private function assertData( array $data ) {
		if ( !isset( $data['title'] ) || !$this->setTitle( $data['title'] ) ) {
			throw new WorkflowExecutionException(
				Message::newFromKey( 'workflows-activity-error-no-title' )->text(),
				$this->getTask()
			);
		}
		if ( isset( $data['user'] ) ) {
			$this->user = $this->userFactory->newFromName( $data['user'] );
			if ( $this->user && !$this->permissionManager->userCan( 'edit', $this->user, $this->title ) ) {
				throw new WorkflowExecutionException(
					Message::newFromKey( 'workflows-activity-cannot-edit' )
						->params( $this->user->getName(), $this->title->getPrefixedText() )->text(),
					$this->getTask()
				);
			}
		} else {
			$this->user = User::newSystemUser( 'Mediawiki default' );
		}

		$this->templateIndex = (int)( $data['template-index'] ?? -1 );
		$this->templateParamIndex = $data['template-param'] ?? -1;
		if ( is_numeric( $this->templateParamIndex ) ) {
			$this->templateParamIndex = (int)$this->templateParamIndex;
		}
		$this->value = $data['value'] ?? '';
		$this->isMinor = (bool)( $data['minor'] ?? false );
		$this->comment = $data['comment'] ?? '';
	}

	/**
	 * @param string $title
	 * @return bool
	 */
	private function setTitle( $title ) {
		$this->title = $this->titleFactory->newFromText( $title );
		return $this->title instanceof Title && $this->title->exists();
	}

}
