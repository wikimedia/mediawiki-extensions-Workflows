<?php

namespace MediaWiki\Extension\Workflows\Tests\Activity;

use MediaWiki\Extension\Workflows\Activity\EditPageActivity;
use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\Element\Task;
use MediaWiki\Extension\Workflows\Logger\ISpecialLogLogger;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Extension\Workflows\WorkflowContextMutable;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\UserFactory;
use MediaWikiIntegrationTestCase;
use TitleFactory;
use User;

/**
 * @covers \MediaWiki\Extension\Workflows\Activity\EditPageActivity
 * @group Database
 */
class EditPageActivityTest extends MediaWikiIntegrationTestCase {
	/** @var User */
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->insertPage(
			'Dummy page',
			'This is my page'
		);
		$this->user = $this->getTestSysop()->getUser();
	}

	/**
	 * @param array $data
	 * @param array $expected
	 *
	 * @covers       \MediaWiki\Extension\Workflows\Activity\EditPageActivity::execute
	 * @dataProvider provideCompleteItemData
	 *
	 * @throws \MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException
	 */
	public function testCompleteItem( $data, $expected ) {
		$data['user'] = $this->user->getName();
		$spclLogLoggerMock = $this->createMock( ISpecialLogLogger::class );
		$spclLogLoggerMock->expects( $this->once() )->method( 'addEntry' );

		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->method( 'newFromText' )->willReturn( \Title::newFromDBkey( 'Dummy_page' ) );
		$userFactoryMock = $this->createMock( UserFactory::class );
		$userFactoryMock->method( 'newFromName' )->willReturn( $this->user );
		$permissionManagerMock = $this->createMock( PermissionManager::class );
		$permissionManagerMock->method( 'userCan' )->willReturn( true );

		$task = new Task( 'Test_Id', 'editPage', [], [], 'task' );
		$activity = new EditPageActivity( $titleFactoryMock, $userFactoryMock, $permissionManagerMock, $task );
		$activity->setSpecialLogLogger( $spclLogLoggerMock );

		$definitionContext = new DefinitionContext( [] );
		$mutableContext = new WorkflowContextMutable( $titleFactoryMock );
		$mutableContext->setDefinitionContext( $definitionContext );
		$workflowContext = new WorkflowContext( $mutableContext );
		$status = $activity->execute( $data, $workflowContext );
		$this->assertArrayEquals( $expected, array_keys( $status->getPayload() ) );
		$this->assertSame( 'Dummy_page', $status->getPayload()['title'] );
	}

	/**
	 *
	 * @return array
	 */
	public function provideCompleteItemData() {
		return [
			'test1' => [
				'data' => [
					'title' => 'Dummy page',
					'content' => "My page content",
					'mode' => 'append',
				],
				'expected' => [
					'revisionId', 'title', 'timestamp'
				]
			]
		];
	}
}
