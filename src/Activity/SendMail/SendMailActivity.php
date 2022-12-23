<?php

namespace MediaWiki\Extension\Workflows\Activity\SendMail;

use Config;
use MailAddress;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\GenericActivity;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\Logger\SpecialLogLoggerAwareInterface;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Mail\IEmailer;
use MediaWiki\MediaWikiServices;
use MWException;
use Title;
use User;

class SendMailActivity extends GenericActivity implements SpecialLogLoggerAwareInterface {

	/** @var IEmailer */
	private $emailer;
	/** @var MailAddress */
	private $fromAddress;
	/** @var User */
	private $logActor;
	/** @var ISpecialLogLogger */
	private $specialLogLogger;

	/**
	 * @param IEmailer $emailer
	 * @param Config $config
	 * @param ITask $task
	 */
	public function __construct( IEmailer $emailer, Config $config, ITask $task ) {
		parent::__construct( $task );

		$this->logActor = User::newSystemUser( 'Mediawiki default' );
		$this->emailer = $emailer;
		$this->fromAddress = new MailAddress(
			$config->get( 'NoReplyAddress' )
		);
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

	/**
	 * @param array $data
	 * @param WorkflowContext $context
	 * @return ExecutionStatus
	 * @throws MWException In cases of some invalid values
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$to = $data['recipient'];
		if ( empty( $to ) ) {
			return new ExecutionStatus( static::STATUS_COMPLETE );
		}
		if ( !is_array( $to ) ) {
			$to = [ $to ];
		}
		$to = array_map( static function ( $to ) {
			if ( strpos( $to, '@' ) !== false ) {
				// Very basic check for email address
				return new MailAddress( $to );
			}
			$toUser = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $to );
			if ( $toUser instanceof User && $toUser->isRegistered() && $toUser->isEmailConfirmed() ) {
				return MailAddress::newFromUser( $toUser );
			}
			return null;
		}, $to );

		$to = array_filter( $to, static function ( $to ) {
			return $to instanceof MailAddress;
		} );
		$from = $this->fromAddress;
		$subject = $data['subject'];
		$bodyText = $data[ 'body' ];

		$bodyText = strip_tags( $bodyText );

		$status = $this->emailer->send( $to, $from, $subject, $bodyText );
		if ( $status->isGood() ) {
			$this->getSpecialLogLogger()->addEntry(
				'sendmail-send',
				Title::makeTitle( NS_SPECIAL, 'Badtitle' ),
				$this->logActor,
				'',
				[
					'4::subject' => $subject,
					'recipients' => implode( ', ', $to )
				]
			);
			$this->logger->debug( 'Send mail "{subject}" from {from} to {to}.', [
				'subject' => $subject,
				'from' => $from,
				'to' => $to
			] );
		} else {
			$this->logger->error(
				'Could not send mail "{subject}" from {from} to {to}: {errors}',
				[
					'subject' => $subject,
					'from' => $from,
					'to' => $to,
					'errors' => $status->getErrors()
				]
			);
		}

		return new ExecutionStatus( static::STATUS_COMPLETE );
	}
}
