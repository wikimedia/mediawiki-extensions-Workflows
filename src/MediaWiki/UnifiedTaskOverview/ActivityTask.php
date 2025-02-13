<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\UnifiedTaskOverview;

use Exception;
use MediaWiki\Extension\UnifiedTaskOverview\ITaskDescriptor;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageProps;
use MediaWiki\Title\Title;

class ActivityTask implements ITaskDescriptor {
	/** @var UserInteractiveActivity */
	protected $activity;
	/** @var Workflow */
	protected $workflow;
	/** @var Title|null */
	protected $title = null;
	/** @var Revision|null */
	protected $revision = null;

	/** @var PageProps */
	private PageProps $pageProps;

	/**
	 * @param UserInteractiveActivity $activity
	 * @param Workflow $workflow
	 */
	public function __construct( UserInteractiveActivity $activity, Workflow $workflow ) {
		$this->activity = $activity;
		$this->workflow = $workflow;
		$this->pageProps = MediaWikiServices::getInstance()->getPageProps();

		$this->trySetTitle();
	}

	protected function trySetTitle() {
		$title = $this->workflow->getContext()->getContextPage();
		if ( $title instanceof Title ) {
			$this->title = $title;
			$this->revision = $this->workflow->getContext()->getDefinitionContext()->getItem( 'revision' );
		}
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return 'workflows-activity-' . $this->getActivityType();
	}

	/**
	 * @return string
	 */
	public function getURL(): string {
		$query = [];
		if ( $this->revision !== null ) {
			$query['oldid'] = (int)$this->revision;
		}
		return $this->title ? $this->title->getFullURL( $query ) : '#';
	}

	/**
	 * @return Message
	 */
	public function getHeader(): Message {
		if ( !$this->title ) {
			return new RawMessage( '' );
		}

		$displayTitleProperties = $this->pageProps->getProperties( $this->title, 'displaytitle' );
		if ( count( $displayTitleProperties ) === 1 ) {
			$displayTitle = $displayTitleProperties[$this->title->getArticleID()];
		}

		return new RawMessage( $displayTitle ?? $this->title->getSubpageText() );
	}

	/**
	 * @return Message
	 * @throws Exception
	 */
	public function getSubHeader(): Message {
		// workflows-uto-activity-custom_form
		// workflows-uto-activity-user_vote
		// workflows-uto-activity-group_vote
		// workflows-uto-activity-user_feedback
		// workflows-uto-activity-group_feedback
		return Message::newFromKey(
			'workflows-uto-activity-' . $this->getActivityType()
		);
	}

	/**
	 * @return Message
	 */
	public function getBody(): Message {
		$messages = $this->getBodyMessages();
		$body = [];
		foreach ( $messages as $message ) {
			if ( $message instanceof Message ) {
				$body[] = $message->text();
			}
			if ( is_string( $message ) ) {
				$body[] = $message;
			}
		}

		return new RawMessage(
			implode( "\n", array_map( static function ( $a ) {
				return '* ' . $a;
			}, $body ) )
		);
	}

	/**
	 * Array of messages to be inserted to body
	 * @return array
	 */
	protected function getBodyMessages() {
		$initiator = $this->workflow->getContext()->getInitiator();
		if ( !$initiator ) {
			return [];
		}

		return [
			Message::newFromKey( 'workflows-uto-activity-body-initiator', $initiator, $initiator->getName() )
		];
	}

	/**
	 * @return int
	 */
	public function getSortKey(): int {
		return 10;
	}

	/**
	 * @return array
	 */
	public function getRLModules(): array {
		return [ 'ext.workflows.uto.styles' ];
	}

	/**
	 * @return string
	 */
	private function getActivityType(): string {
		return $this->activity->getTask()->getExtensionElements()['type'] ?? 'generic';
	}
}
