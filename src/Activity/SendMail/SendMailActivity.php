<?php

namespace MediaWiki\Extension\Workflows\Activity\SendMail;

use Exception;
use MailAddress;
use MediaWiki\Config\Config;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\GenericActivity;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\Logger\SpecialLogLoggerAwareInterface;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Mail\IEmailer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

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

		$this->logActor = User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] );
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
	 * @throws Exception In cases of some invalid values
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$to = $data['recipient'];
		if ( empty( $to ) ) {
			$this->logger->debug( 'No recipient given, skipping mail.', $data );
			return new ExecutionStatus( static::STATUS_COMPLETE );
		}
		$to = explode( '|', $to );
		$to = array_map( static function ( $to ) {
			$to = trim( $to );
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
		$format = $data['format'] ?? 'plaintext';

		$options = [];
		$htmlBody = null;
		if ( $format === 'html' ) {
			$htmlBody = $this->wrapHtml( $bodyText );
			$options['contentType'] = 'text/html;charset=UTF-8';
		} else {
			$bodyText = strip_tags( $bodyText );
		}

		$status = $this->emailer->send( $to, $from, $subject, $bodyText, $htmlBody, $options );
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

	/**
	 * @param string $body
	 * @return string
	 */
	private function wrapHtml( string $body ): string {
		// Make a valid html mail
		$res = '<html><head><meta charset="UTF-8"></head><body>';
		$res .= $body;
		$res .= '</body></html>';
		return $res;
	}

}
