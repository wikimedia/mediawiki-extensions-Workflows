<?php

namespace MediaWiki\Extension\Workflows\Activity;

use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MWContentSerializationException;
use MWStake\MediaWiki\Component\Wikitext\Node\Transclusion;
use MWStake\MediaWiki\Component\Wikitext\ParserFactory;
use RuntimeException;

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
	/** @var array */
	private $templateParams = [];
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
	 * @throws LogicException
	 * @throws RuntimeException
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
		$templates = array_values( array_filter( $templates, static function ( $node ) {
			return $node instanceof Transclusion;
		} ) );

		if ( empty( $templates ) || !isset( $templates[$this->templateIndex] ) ) {
			throw new WorkflowExecutionException(
				Message::newFromKey( 'workflows-activity-set-template-params-no-target' )->text(),
				$this->getTask()
			);
		}
		/** @var Transclusion $node */
		$node = $templates[$this->templateIndex];
		foreach ( $this->templateParams as $paramIndex => $paramValue ) {
			$paramIndex = is_numeric( $paramIndex ) ? (int)$paramIndex : $paramIndex;
			$node->setParam( $paramIndex, $paramValue );
		}
		$parser->replaceNode( $node );
		$rev = $parser->saveRevision( $this->user, $this->comment, $this->isMinor ? EDIT_MINOR : 0 );
		if ( !( $rev instanceof RevisionRecord ) ) {
			$this->logger->error(
				'Workflows: SetTemplateParamsActivity: Failed to save revision, potential error or no change'
			);
			$rev = $revision;
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
		if ( !empty( $data['user'] ) ) {
			$this->user = $this->userFactory->newFromName( $data['user'] );
			if ( $this->user && !$this->permissionManager->userCan( 'edit', $this->user, $this->title ) ) {
				throw new WorkflowExecutionException(
					Message::newFromKey( 'workflows-activity-cannot-edit' )
						->params( $this->user->getName(), $this->title->getPrefixedText() )->text(),
					$this->getTask()
				);
			}
		} else {
			$this->user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
		}

		$this->templateIndex = (int)( $data['template-index'] ?? -1 );
		$this->templateParams = [];
		if ( !empty( $data['template-params'] ) ) {
			$params = $data['template-params'];
			if ( is_string( $params ) ) {
				$decoded = json_decode( $params, true );
				if ( is_array( $decoded ) ) {
					$this->templateParams = $decoded;
				}
			} elseif ( is_array( $params ) ) {
				$this->templateParams = $params;
			}
		} elseif ( isset( $data['template-param'] ) && isset( $data['value'] ) ) {
			$key = $data['template-param'];
			$this->templateParams[$key] = $data['value'];
		}
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
