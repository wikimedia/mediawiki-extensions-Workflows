<?php

namespace MediaWiki\Extension\Workflows\Tests\Activity;

use MediaWiki\Extension\Workflows\Activity\SetTemplateParamsActivity;
use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\Element\Task;
use MediaWiki\Extension\Workflows\IActivity;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Extension\Workflows\WorkflowContextMutable;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWikiIntegrationTestCase;
use MWStake\MediaWiki\Component\Wikitext\Node\Transclusion;
use MWStake\MediaWiki\Component\Wikitext\Parser\WikitextParser;
use MWStake\MediaWiki\Component\Wikitext\ParserFactory;

/**
 * @covers \MediaWiki\Extension\Workflows\Activity\SetTemplateParamsActivity
 * @group Database
 */
class SetTemplateParamsActivityTest extends MediaWikiIntegrationTestCase {
	/** @var User */
	private $user;

	/** @var Title */
	private $title;

	protected function setUp(): void {
		parent::setUp();

		$this->insertPage(
			'Dummy page',
			'{{DummyTemplate|bearbeiter=User:Admin|prüfer=User:Admin|freigebender=User:Admin}}'
		);

		$this->user = $this->getTestSysop()->getUser();
		$this->title = Title::newFromDBkey( 'Dummy_page' );
	}

	/**
	 * @covers \MediaWiki\Extension\Workflows\Activity\SetTemplateParamsActivity::execute
	 */
	public function testExecuteSetsMultipleTemplateParams() {
		$setParamCalls = [];

		$revision = $this->createMock( RevisionRecord::class );
		$revision->method( 'getId' )->willReturn( 1 );
		$revision->method( 'getTimestamp' )->willReturn( '20260101000000' );

		$newRevision = $this->createMock( RevisionRecord::class );
		$newRevision->method( 'getId' )->willReturn( 2 );
		$newRevision->method( 'getTimestamp' )->willReturn( '20260101000001' );

		$transclusion = $this->createMock( Transclusion::class );
		$transclusion->expects( $this->exactly( 3 ) )
			->method( 'setParam' )
			->willReturnCallback( static function ( $paramIndex, $paramValue ) use ( &$setParamCalls ) {
				$setParamCalls[] = [ $paramIndex, $paramValue ];

				return true;
			} );

		$parser = $this->getMockBuilder( WikitextParser::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'parse', 'replaceNode', 'saveRevision' ] )
			->getMock();

		$parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( [ $transclusion ] );
		$parser->expects( $this->once() )
			->method( 'replaceNode' )
			->with( $transclusion );
		$parser->expects( $this->once() )
			->method( 'saveRevision' )
			->with( $this->user, 'Test edit', 0 )
			->willReturn( $newRevision );

		$activity = $this->prepareActivity( $revision, $parser );

		$status = $activity->execute( [
			'user' => $this->user->getName(),
			'title' => 'Dummy page',
			'template-index' => '0',
			'template-params' => json_encode( [
				'bearbeiter' => 'User:Editor',
				'prüfer' => 'User:Reviewer',
				'freigebender' => 'User:Approver',
			] ),
			'minor' => false,
			'comment' => 'Test edit',
		], $this->getWorkflowContext() );

		$this->assertSame( IActivity::STATUS_COMPLETE, $status->getStatus() );
		$this->assertSame( 2, $status->getPayload()['revisionId'] );
		$this->assertSame( '20260101000001', $status->getPayload()['timestamp'] );
		$this->assertSame( [
			[ 'bearbeiter', 'User:Editor' ],
			[ 'prüfer', 'User:Reviewer' ],
			[ 'freigebender', 'User:Approver' ],
		], $setParamCalls );
	}

	/**
	 * @covers \MediaWiki\Extension\Workflows\Activity\SetTemplateParamsActivity::execute
	 */
	public function testExecuteSupportsLegacyTemplateParamInput() {
		$setParamCalls = [];

		$revision = $this->createMock( RevisionRecord::class );
		$revision->method( 'getId' )->willReturn( 1 );
		$revision->method( 'getTimestamp' )->willReturn( '20260101000000' );

		$newRevision = $this->createMock( RevisionRecord::class );
		$newRevision->method( 'getId' )->willReturn( 2 );
		$newRevision->method( 'getTimestamp' )->willReturn( '20260101000001' );

		$transclusion = $this->createMock( Transclusion::class );
		$transclusion->expects( $this->once() )
			->method( 'setParam' )
			->willReturnCallback( static function ( $paramIndex, $paramValue ) use ( &$setParamCalls ) {
				$setParamCalls[] = [ $paramIndex, $paramValue ];

				return true;
			} );

		$parser = $this->getMockBuilder( WikitextParser::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'parse', 'replaceNode', 'saveRevision' ] )
			->getMock();

		$parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( [ $transclusion ] );
		$parser->expects( $this->once() )
			->method( 'replaceNode' )
			->with( $transclusion );
		$parser->expects( $this->once() )
			->method( 'saveRevision' )
			->with( $this->user, 'Test edit', 0 )
			->willReturn( $newRevision );

		$activity = $this->prepareActivity( $revision, $parser );

		$status = $activity->execute( [
			'user' => $this->user->getName(),
			'title' => 'Dummy page',
			'template-index' => '0',
			'template-param' => 'bearbeiter',
			'value' => 'User:Editor',
			'minor' => false,
			'comment' => 'Test edit',
		], $this->getWorkflowContext() );

		$this->assertSame( IActivity::STATUS_COMPLETE, $status->getStatus() );
		$this->assertSame( [
			[ 'bearbeiter', 'User:Editor' ],
		], $setParamCalls );
	}

	/**
	 * @param RevisionRecord $revision
	 * @param WikitextParser $parser
	 * @return SetTemplateParamsActivity
	 */
	private function prepareActivity(
		RevisionRecord $revision,
		WikitextParser $parser
	): SetTemplateParamsActivity {
		$parserFactoryMock = $this->createMock( ParserFactory::class );
		$parserFactoryMock->method( 'newRevisionParser' )->willReturn( $parser );

		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->method( 'newFromText' )->willReturn( $this->title );

		$revisionStoreMock = $this->createMock( RevisionStore::class );
		$revisionStoreMock->method( 'getRevisionByTitle' )->willReturn( $revision );

		$userFactoryMock = $this->createMock( UserFactory::class );
		$userFactoryMock->method( 'newFromName' )->willReturn( $this->user );

		$permissionManagerMock = $this->createMock( PermissionManager::class );
		$permissionManagerMock->method( 'userCan' )->willReturn( true );

		$task = new Task( 'Test_Id', 'setParam', [], [], 'task' );

		return new SetTemplateParamsActivity(
			$parserFactoryMock,
			$titleFactoryMock,
			$revisionStoreMock,
			$userFactoryMock,
			$permissionManagerMock,
			$task
		);
	}

	/**
	 * @return WorkflowContext
	 */
	private function getWorkflowContext(): WorkflowContext {
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$definitionContext = new DefinitionContext( [] );
		$mutableContext = new WorkflowContextMutable( $titleFactoryMock );
		$mutableContext->setDefinitionContext( $definitionContext );

		return new WorkflowContext( $mutableContext );
	}
}
