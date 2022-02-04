<?php

namespace MediaWiki\Extension\Workflows\Activity\FeedbackActivity;

use MediaWiki\Extension\Workflows\Activity\UIActivity;
use MediaWiki\Extension\Workflows\ActivityDescriptor\FeedbackDescriptor;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\IActivityDescriptor;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\Logger\SpecialLogLoggerAwareInterface;
use MediaWiki\Extension\Workflows\WorkflowContext;
use Message;
use MWStake\MediaWiki\Component\Notifications\INotifier;
use Title;
use User;

abstract class GenericFeedbackActivity extends UIActivity implements SpecialLogLoggerAwareInterface {
	/**
	 * String, which specifies vote activity executed.
	 * Used in keys of some internal data or exception messages.
	 *
	 * @var string
	 */
	protected $activityKey = '';
	/**
	 * Target page which is being reviewed
	 *
	 * @var Title
	 */
	protected $targetPage;
	/**
	 * User which performs specified action
	 *
	 * @var User
	 */
	protected $actor;
	/**
	 * User which is used to create logs in special log page
	 *
	 * @var User
	 */
	protected $logActor;
	/**
	 * User-owner of review process
	 *
	 * @var User
	 */
	protected $owner;
	/** @var string */
	protected $action;
	/** @var ISpecialLogLogger */
	private $specialLogLogger;
	/** @var INotifier */
	private $notifier;

	/**
	 * @param INotifier $notifier
	 * @param ITask $task
	 */
	public function __construct( INotifier $notifier, ITask $task ) {
		parent::__construct( $task );

		$this->logActor = User::newSystemUser( 'Mediawiki default' );
		$this->notifier = $notifier;
	}

	/**
	 * @inheritDoc
	 */
	public function setSpecialLogLogger( ISpecialLogLogger $logger ) {
		$this->specialLogLogger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function getSpecialLogLogger(): ISpecialLogLogger {
		return $this->specialLogLogger;
	}

	/**
	 * @return INotifier
	 */
	protected function getNotifier(): INotifier {
		return $this->notifier;
	}

	/**
	 * Sets data, necessary for vote processing.
	 * Also makes some checks on input and collects possible errors
	 *
	 * @param array $data Data which is used to process activity
	 * @param WorkflowContext $context Context object
	 * @throws WorkflowExecutionException In case of some invalid values
	 */
	abstract protected function setSecondaryData( array $data, WorkflowContext $context ): void;

	/**
	 * Checks and sets all necessary context data, used for vote activity.
	 *
	 * @param array $data Data which is used to process activity
	 * @param WorkflowContext $context Context object
	 * @throws WorkflowExecutionException In case of some invalid values
	 */
	protected function setPrimaryData( array $data, WorkflowContext $context ): void {
		$errorMessages = [];

		$page = $context->getDefinitionContext()->getItem( 'pageId' );
		$target = Title::newFromId( $page );
		if ( $target instanceof Title && $target->exists() ) {
			$this->targetPage = $target;
		} else {
			// workflows-group-vote-target-title-invalid
			// workflows-user-vote-target-title-invalid
			$errorMessages[] = 'workflows-' . $this->activityKey . '-target-title-invalid';
		}

		$this->owner = $context->getInitiator();

		$this->actor = $context->getCurrentActor();

		$this->handleErrors( $errorMessages );
	}

	/**
	 * Logs error messages and throws exception
	 *
	 * @param string[] $errorMessages List of error messages. Can be empty if there are no errors
	 * @throws WorkflowExecutionException In cases if some errors are presented
	 */
	protected function handleErrors( array $errorMessages ): void {
		if ( $errorMessages ) {
			$errorMessages = array_map( static function ( $key ) {
				return Message::newFromKey( $key )->text();
			}, $errorMessages );
			foreach ( $errorMessages as $errorMessage ) {
				$this->logger->error( $errorMessage );
			}

			// We cannot output all errors at once in exception.
			// So all errors will be logged, but only first of them will be displayed in exception
			throw new WorkflowExecutionException( $errorMessages[0], $this->task );
		}
	}

	protected function logToSpecialLog( string $action, string $comment, array $params = [] ): void {
		$this->getSpecialLogLogger()->addEntry(
			$action,
			$this->targetPage,
			$this->actor ?? $this->logActor,
			$comment,
			$params
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getActivityDescriptor(): IActivityDescriptor {
		return new FeedbackDescriptor( $this, $this->logger );
	}
}
