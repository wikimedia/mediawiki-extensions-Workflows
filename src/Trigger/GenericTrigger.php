<?php

namespace MediaWiki\Extension\Workflows\Trigger;

use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\Exception\WorkflowTriggerException;
use MediaWiki\Extension\Workflows\ITrigger;
use MediaWiki\Extension\Workflows\UserInteractiveActivity;
use MediaWiki\Extension\Workflows\Util\DataPreprocessor;
use MediaWiki\Extension\Workflows\Util\DataPreprocessorContext;
use MediaWiki\Extension\Workflows\Workflow;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\MediaWikiServices;
use Message;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Title;
use TitleFactory;

class GenericTrigger implements ITrigger, LoggerAwareInterface {
	/** @var WorkflowFactory */
	protected $workflowFactory;
	/** @var string */
	protected $type = '';
	/** @var string */
	protected $id;
	/** @var string */
	protected $name;
	/** @var string */
	protected $description;
	/** @var string */
	protected $definition;
	/** @var string */
	protected $repo;
	/** @var array */
	protected $contextData;
	/** @var array */
	protected $initData;
	/** @var array */
	protected $rules;
	/** @var bool */
	private $active;
	/** @var array */
	private $edtiorData;
	/** @var LoggerInterface */
	protected $logger;
	/** @var TitleFactory */
	protected $titleFactory;

	/**
	 * @param TitleFactory $titleFactory
	 * @param string $id
	 * @param array $data
	 * @return static
	 */
	public static function factory( TitleFactory $titleFactory, $id, $data ) {
		$instance = new static(
			$titleFactory,
			$id,
			$data['name'],
			$data['description'] ?? '',
			$data['type'],
			$data['definition'],
			$data['repository'],
			$data['contextData'] ?? [],
			$data['initData'] ?? [],
			$data['rules'] ?? [],
			isset( $data['active'] ) ? (bool)$data['active'] : true,
			$data['editorData'] ?? []
		);

		return $instance;
	}

	/**
	 * @param TitleFactory $titleFactory
	 * @param string $id
	 * @param string $name
	 * @param string $description
	 * @param string $type
	 * @param string $definition
	 * @param string $repo
	 * @param array $contextData
	 * @param array $initData
	 * @param array $rules
	 * @param bool $active
	 * @param array $editorData
	 */
	public function __construct(
		TitleFactory $titleFactory, $id, $name, $description, $type, $definition, $repo,
		$contextData, $initData, $rules, $active = true, $editorData = []
	) {
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
		$this->type = $type;
		$this->definition = $definition;
		$this->repo = $repo;
		$this->contextData = $contextData;
		$this->initData = $initData;
		$this->rules = $rules;
		$this->active = $active;
		$this->edtiorData = $editorData;

		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param WorkflowFactory $workflowFactory
	 */
	public function setWorkflowFactory( WorkflowFactory $workflowFactory ) {
		$this->workflowFactory = $workflowFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * @return bool
	 * @throws WorkflowTriggerException
	 */
	public function trigger(): bool {
		if ( !$this->workflowFactory ) {
			throw new WorkflowTriggerException( 'Workflow factory not set', $this );
		}
		try {
			return $this->startWorkflow(
				$this->repo, $this->definition, $this->getContextData(), $this->initData
			);
		} catch ( WorkflowExecutionException $ex ) {
			$this->logger->error( $ex->getMessage(), [
				'repository' => $this->repo,
				'definition' => $this->definition,
				'contextData' => $this->getContextData(),
				'initData' => $this->initData,
			] );
			return false;
		}
	}

	/**
	 * @return array
	 */
	protected function getContextData() {
		return $this->contextData;
	}

	/**
	 * @inheritDoc
	 */
	public function getNativeType(): string {
		return $this->getType();
	}

	/**
	 * @param string $repo
	 * @param string $definition
	 * @param array $contextData
	 * @param array|null $initData
	 * @return string
	 * @throws \MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException
	 */
	protected function startWorkflow( $repo, $definition, $contextData = [], $initData = null ): string {
		$workflow = $this->workflowFactory->newEmpty( $definition, $repo );
		if ( $this->isAutomatic() ) {
			$workflow->markAsBotProcess();
		}
		$workflow->start( $contextData );
		$initializer = $this->getInitializer( $workflow );
		if ( $initData && $initializer ) {
			$workflow->completeTask( $initializer->getTask(), $initData );
		}
		$this->workflowFactory->persist( $workflow );
		return $workflow->getStorage()->aggregateRootId()->toString();
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getAttributes(): array {
		return [
			'contextData' => $this->contextData,
			'initData' => $this->initData,

		];
	}

	/**
	 * @return array
	 */
	public function getRuleset(): array {
		return $this->rules;
	}

	/**
	 * @param Workflow $engine
	 * @return \MediaWiki\Extension\Workflows\IActivity|UserInteractiveActivity|null
	 * @throws \Exception
	 */
	protected function getInitializer( Workflow $engine ) {
		$currentTasks = $engine->current();
		foreach ( $currentTasks as $id => $item ) {
			if ( $item instanceof ITask ) {
				$activity = $engine->getActivityForTask( $item );
				if ( $activity instanceof UserInteractiveActivity && $activity->isInitializer() ) {
					return $activity;
				}
			}
		}

		return null;
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @return bool
	 */
	public function isActive(): bool {
		return $this->active;
	}

	/**
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isAutomatic(): bool {
		return true;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		$data = [
			'type' => $this->getType(),
			'name' => $this->getName(),
			'name_parsed' => $this->tryGetTranslation( $this->getName() ),
			'description' => $this->getDescription(),
			'description_parsed' => $this->tryGetTranslation( $this->getDescription() ),
			'active' => $this->active,
			'definition' => $this->definition,
			'repository' => $this->repo,
			'initData' => $this->initData,
			'contextData' => $this->contextData,
			'editorData' => $this->edtiorData,
		];
		if ( !empty( $this->rules ) ) {
			$data['rules'] = $this->rules;
		}
		return $data;
	}

	/**
	 * @param Title $title
	 * @param array $data
	 * @param array $qualifyingData
	 * @return bool
	 */
	protected function titleFits( Title $title, array $data, $qualifyingData = [] ) {
		foreach ( $data as $type => $value ) {
			switch ( $type ) {
				case 'namespace':
					if ( !is_array( $value ) ) {
						$value = [ $value ];
					}
					if ( in_array( $title->getNamespace(), $value ) ) {
						return true;
					}
					break;
				case 'category':
					if ( !is_array( $value ) ) {
						$value = [ $value ];
					}
					if ( empty( $value ) ) {
						break;
					}
					$belongsTo = array_map( static function ( $category ) {
						$bits = explode( ':', $category );
						return array_pop( $bits );
					}, array_keys( $title->getParentCategories() ) );
					$value = array_map( static function ( $category ) {
						return str_replace( ' ', '_', $category );
					}, $value );
					if ( !empty( array_intersect( $belongsTo, $value ) ) ) {
						return true;
					}
					break;
				case 'editType':
					if ( !isset( $qualifyingData['editType'] ) ) {
						return false;
					}
					if ( $qualifyingData['editType'] === $value ) {
						return true;
					}
					break;
				case 'pages':
					$pages = $this->processPagesRule( $value, $title );
					foreach ( $pages as $allowedTitle ) {
						if ( $allowedTitle->getPrefixedDBkey() === $title->getPrefixedDBkey() ) {
							return true;
						}
					}
			}
		}

		return false;
	}

	/**
	 * @param Title $title
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function appliesToPage( Title $title, $qualifyingData = [] ): bool {
		if ( isset( $this->rules['include'] ) && !empty( $this->rules['include'] ) ) {
			$included = $this->titleFits( $title, $this->rules['include'], $qualifyingData );
		} else {
			// In no includes are specified, everything is included
			$included = true;
		}
		if ( !$included || !isset( $this->rules['exclude'] ) || empty( $this->rules['exclude'] ) ) {
			return $included;
		}

		return !$this->titleFits( $title, $this->rules['exclude'], $qualifyingData );
	}

	/**
	 * @param string $value
	 * @param Title|null $title
	 * @return array
	 */
	protected function processPagesRule( $value, ?Title $title = null ): array {
		/** @var DataPreprocessor $preprocessor */
		$preprocessor = MediaWikiServices::getInstance()->getService( 'WorkflowsDataPreprocessor' );
		$context = new DataPreprocessorContext( $title );
		$processed = $preprocessor->preprocess( [ 'value' => $value ], [], $context );
		if ( !isset( $processed['value'] ) || !is_string( $processed['value'] ) ) {
			$this->logger->warning( 'Cannot process trigger rule: ' . $value );
			return [];
		}

		$list = explode( '|', $processed['value'] );
		$list = array_map( static function ( $item ) {
			return trim( $item );
		}, $list );
		$res = [];
		foreach ( $list as $allowedPagename ) {
			// TODO: This is expensive, but allows for more flexibility when specifying titles
			// Not sure if its worth it, needs to be re-evaluated
			$allowedTitle = $this->titleFactory->newFromText( $allowedPagename );
			if ( !( $allowedTitle instanceof Title ) ) {
				continue;
			}
			$res[] = $allowedTitle;
		}

		return $res;
	}

	/**
	 * Check if key is a message key and parse it if it is.
	 * @param string $text
	 *
	 * @return string
	 */
	private function tryGetTranslation( string $text ): string {
		if ( empty( $text ) ) {
			return '';
		}

		$msg = Message::newFromKey( $text );
		if ( !$msg->exists() ) {
			return $text;
		}

		return $msg->text();
	}
}
