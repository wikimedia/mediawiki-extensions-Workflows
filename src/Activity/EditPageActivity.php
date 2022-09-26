<?php

namespace MediaWiki\Extension\Workflows\Activity;

use CommentStoreComment;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\Logger\SpecialLogLoggerAwareInterface;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserFactory;
use Message;
use Title;
use TitleFactory;
use User;
use WikitextContent;

class EditPageActivity extends GenericActivity implements SpecialLogLoggerAwareInterface {
	private const MODE_REPLACE = 'replace';
	private const MODE_APPEND = 'append';
	private const MODE_PREPEND = 'prepend';

	/** @var TitleFactory */
	private $titleFactory;
	/** @var UserFactory */
	private $userFactory;
	/** @var PermissionManager */
	private $permissionManager;
	/** @var ISpecialLogLogger */
	private $specialLogLogger;
	/** @var Title */
	private $title;
	/** @var User */
	private $user;
	/** @var string */
	private $newText;
	/** @var string */
	private $mode;
	/** @var bool */
	private $minor = false;

	/**
	 * @param TitleFactory $titleFactory
	 * @param UserFactory $userFactory
	 * @param PermissionManager $permissionManager
	 * @param ITask $task
	 */
	public function __construct(
		TitleFactory $titleFactory, UserFactory $userFactory,
		PermissionManager $permissionManager, ITask $task
	) {
		parent::__construct( $task );
		$this->titleFactory = $titleFactory;
		$this->userFactory = $userFactory;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$this->processData( $data );

		$wikiPage = MediaWikiServices::getInstance()->getWikiPageFactory()
			->newFromTitle( $this->title );
		$updater = $wikiPage->newPageUpdater( $this->user );

		$content = $wikiPage->getContent( SlotRecord::MAIN );
		if ( $this->title->exists() && !( $content instanceof WikitextContent ) ) {
			throw new WorkflowExecutionException(
				Message::newFromKey( 'workflows-activity-editpage-error-invalid-cm' )->text(),
				$this->getTask()
			);
		}
		$text = '';
		if ( $this->title->exists() && $this->mode !== static::MODE_REPLACE ) {
			/** @var WikitextContent $content */
			$text = $content->getText();
		}

		switch ( $this->mode ) {
			case static::MODE_APPEND:
				$text = "$text\n$this->newText";
				break;
			case static::MODE_PREPEND:
				$text = "$this->newText\n$text";
				break;
			default:
				$text = $this->newText;
		}

		$newContent = new WikitextContent( $text );
		$updater->setContent( SlotRecord::MAIN, $newContent );
		$revision = $updater->saveRevision(
			CommentStoreComment::newUnsavedComment(
				Message::newFromKey( 'workflows-activity-editpage-summary' )
			),
			$this->minor ? EDIT_MINOR : 0
		);

		if ( $revision === null ) {
			throw new WorkflowExecutionException(
				$updater->getStatus()->getMessage()->text(),
				$this->getTask()
			);
		}

		$this->getSpecialLogLogger()->addEntry(
			'editpage-edit',
			$this->title,
			$this->user,
			'',
			[
				'4::page' => $this->title->getPrefixedDBkey()
			]
		);
		$this->logger->debug( 'Page edited "{page}", created revision {revision}', [
			'page' => $this->title->getPrefixedDBkey(),
			'revision' => $revision->getId()
		] );

		return new ExecutionStatus( IActivity::STATUS_COMPLETE, [
			'revisionId' => $revision->getId(),
			'title' => $this->title->getPrefixedDBkey(),
			'timestamp' => $revision->getTimestamp()
		] );
	}

	private function processData( $data ) {
		if ( !isset( $data['title'] ) ) {
			throw new WorkflowExecutionException(
				Message::newFromKey( 'workflows-activity-editpage-error-no-title' )->text(),
				$this->getTask()
			);
		}
		$this->title = $this->titleFactory->newFromText( $data['title'] );
		if ( !( $this->title instanceof Title ) ) {
			// Previous validation is assumed
			throw new WorkflowExecutionException(
				Message::newFromKey( 'workflows-error-generic' )->text(),
				$this->getTask()
			);
		}
		if ( isset( $data['user'] ) ) {
			$this->user = $this->userFactory->newFromName( $data['user'] );
			if ( !( $this->user instanceof User ) || !$this->user->isRegistered() ) {
				// Previous validation is assumed
				throw new WorkflowExecutionException(
					Message::newFromKey( 'workflows-error-generic' )->text(),
					$this->getTask()
				);
			}
			if ( !$this->permissionManager->userCan( 'edit', $this->user, $this->title ) ) {
				throw new WorkflowExecutionException(
					Message::newFromKey( 'workflows-activity-editpage-permissiondenied' )
					->params( $this->user->getName(), $this->title->getPrefixedText() )->text(),
					$this->getTask()
				);
			}
		} else {
			$this->user = User::newSystemUser( 'MediaWiki default' );
		}

		$this->newText = $data['content'] ?? '';
		$this->mode = $data['mode'] ?? null;
		$this->minor = isset( $data['minor'] ) ? (bool)$data['minor'] : false;
		$isModeValid = in_array(
			$this->mode, [ static::MODE_APPEND, static::MODE_PREPEND, static::MODE_REPLACE ]
		);
		if ( $this->mode === null || !$isModeValid ) {
			$this->mode = static::MODE_APPEND;
		}
	}

	/**
	 * @param ISpecialLogLogger $logger
	 */
	public function setSpecialLogLogger( ISpecialLogLogger $logger ) {
		$this->specialLogLogger = $logger;
	}

	/**
	 * @return ISpecialLogLogger
	 */
	public function getSpecialLogLogger(): ISpecialLogLogger {
		return $this->specialLogLogger;
	}
}
