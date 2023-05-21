<?php

namespace MediaWiki\Extension\Workflows\Tests\Activity;

use HashConfig;
use MailAddress;
use MediaWiki\Extension\Workflows\Activity\SendMail\SendMailActivity;
use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\Element\Task;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Extension\Workflows\WorkflowContextMutable;
use MediaWiki\Mail\IEmailer;
use MediaWikiIntegrationTestCase;
use Status;
use TitleFactory;

/**
 * @covers \MediaWiki\Extension\Workflows\Activity\SendMail\SendMailActivity
 * @group Database
 */
class SendMailActivityTest extends MediaWikiIntegrationTestCase {

	/**
	 *
	 * @param array $data
	 * @param array $expectedMail
	 *
	 * @covers \MediaWiki\Extension\Workflows\Activity\SendMail\SendMailActivity::execute
	 * @dataProvider provideCompleteItemData
	 *
	 */
	public function testCompleteItem( $data, $expectedMail ) {
		$spclLogLoggerMock = $this->createMock( ISpecialLogLogger::class );
		$spclLogLoggerMock->expects( $this->once() )->method( 'addEntry' );

		$emailerMock = $this->createMock( IEmailer::class );
		$emailerMock->expects( $this->once() )->method( 'send' )->with(
			$expectedMail['recipient'],
			new MailAddress( 'noreply@wiki.local' ),
			$expectedMail['subject'],
			$expectedMail['body']
		)->willReturn( Status::newGood() );

		$task = new Task( 'Test_Id', 'Send mail', [], [], 'automaticTask' );
		$config = new HashConfig( [ 'NoReplyAddress' => 'noreply@wiki.local' ] );
		$definitionContext = new DefinitionContext( [] );
		$titleFactory = $this->createMock( TitleFactory::class );
		$mutableContext = new WorkflowContextMutable( $titleFactory );
		$mutableContext->setDefinitionContext( $definitionContext );
		$workflowContext = new WorkflowContext( $mutableContext );

		$activity = new SendMailActivity( $emailerMock, $config, $task );
		$activity->setSpecialLogLogger( $spclLogLoggerMock );
		$activity->execute( $data, $workflowContext );
	}

	/**
	 *
	 * @return array
	 */
	public static function provideCompleteItemData() {
		return [
			'test1' => [
				'data' => [
					'recipient' => 'someone@example.com',
					'subject' => 'Comments on "My cool page"',
					'body' =>
						"* 2021-07-09, 12:00 - Lorem ipsum\n" .
						"* 2021-07-09, 14:00 - Dolor sit amet",
				],
				'expectedMail' => [
					'recipient' => [ new MailAddress( 'someone@example.com' ) ],
					'subject' => 'Comments on "My cool page"',
					'body' =>
						"* 2021-07-09, 12:00 - Lorem ipsum\n" .
						"* 2021-07-09, 14:00 - Dolor sit amet",
				]
			],
			'ERM31686' => [
				'data' => [
					'recipient' => 'someone1@example.com|someone2@example.com|Not#A#Valid#Mail#Address#Or#User|]]',
					'subject' => 'Mutiple recipients',
					'body' => 'Hello World'
				],
				'expectedMail' => [
					'recipient' => [
						new MailAddress( 'someone1@example.com' ),
						new MailAddress( 'someone2@example.com' )
					],
					'subject' => 'Mutiple recipients',
					'body' => 'Hello World'
				]
			]
		];
	}
}
