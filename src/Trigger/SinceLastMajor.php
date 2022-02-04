<?php

namespace MediaWiki\Extension\Workflows\Trigger;

use DateTime;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use Title;
use TitleFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class SinceLastMajor extends PageRelatedTrigger {
	/** @var TitleFactory */
	private $titleFactory;
	/** @var ILoadBalancer */
	private $lb;
	/** @var int */
	private $days;
	/** @var array */
	protected $matches;

	/**
	 * @param ILoadBalancer $lb
	 * @param TitleFactory $titleFactory
	 * @param string $name
	 * @param array $data
	 */
	public function __construct( $lb, $titleFactory, $name, $data ) {
		parent::__construct(
			$name, 'since-last-edit', $data['definition'], $data['repository'],
			$data['contextData'] ?? [], $data['initData'] ?? [],
			$data['rules'] ?? []
		);
		$this->titleFactory = $titleFactory;
		$this->lb = $lb;
		$this->days = (int)$data['days'];
	}

	/**
	 * @return ILoadBalancer
	 */
	protected function getLoadBalancer() {
		return $this->lb;
	}

	/**
	 * @return int
	 */
	protected function getDays() {
		return $this->days;
	}

	/**
	 * @return bool
	 * @throws WorkflowExecutionException
	 */
	public function trigger(): bool {
		/** @var Title $title */
		foreach ( $this->matches as $title ) {
			$this->startWorkflow( $this->repo, $this->definition, [
				'pageId' => $title->getArticleID(),
				'revision' => $title->getLatestRevID(),
			], $this->initData );
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return 'since-last-major';
	}

	/**
	 * @return int[]
	 */
	public function getAttributes(): array {
		return parent::getAttributes() + [
			'days' => $this->getDays()
		];
	}

	/**
	 * @inheritDoc
	 */
	public function appliesToPage( Title $title, $qualifyingData = [] ): bool {
		if ( !$title->isContentPage() ) {
			return false;
		}

		return parent::appliesToPage( $title, $qualifyingData );
	}

	/**
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool {
		$this->loadMatchingPages( $qualifyingData );
		return !empty( $this->matches );
	}

	/**
	 * @param array $qualifyingData
	 */
	protected function loadMatchingPages( $qualifyingData = [] ) {
		$db = $this->getLoadBalancer()->getConnection( DB_REPLICA );
		$current = new DateTime();
		$passed = $current->sub( new \DateInterval( "P{$this->getDays()}D" ) );

		$res = $db->select(
			[ 'p' => 'page', 'r' => 'revision' ],
			[ 'page_id', 'page_title', 'page_namespace' ],
			[
				'rev_timestamp < ' . $db->timestamp( $passed->format( 'YmdHis' ) ),
				'rev_minor_edit = 0'
			],
			__METHOD__,
			[
				'ORDER BY' => 'rev_timestamp DESC'
			],
			[
				'r' => [
					"INNER JOIN", [ 'page_id=rev_page' ]
				]
			]
		);

		$this->matches = [];
		$processed = [];
		foreach ( $res as $row ) {
			// TODO: Exclude duplicates in query
			if ( in_array( $row->page_id, $processed ) ) {
				continue;
			}
			$processed[] = $row->page_id;
			$title = $this->titleFactory->newFromRow( $row );
			if ( $title instanceof \Title && $this->appliesToPage( $title, $qualifyingData ) ) {
				$this->matches[] = $title;
			}
		}
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return parent::jsonSerialize() + [ 'days' => $this->getDays() ];
	}
}
