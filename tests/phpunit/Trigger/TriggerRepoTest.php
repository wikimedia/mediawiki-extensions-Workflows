<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Extension\Workflows\WorkflowFactory;
use MediaWiki\MediaWikiServices;
use Monolog\Logger;

/**
 * Class TriggerRepoTest
 * @package MediaWiki\Extension\Workflows\Tests
 * @group database
 */
class TriggerRepoTest extends \MediaWikiIntegrationTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->insertPage(
			'MediaWiki:WorkflowTriggers',
			file_get_contents( dirname( __DIR__ ) . '/data/triggers.json' )
		);
	}

	/**
	 * @covers \MediaWiki\Extension\Workflows\TriggerRepo::getAll
	 * @covers \MediaWiki\Extension\Workflows\TriggerRepo::getAllOfType
	 */
	public function testGetAll() {
		$repo = $this->getRepo();
		$triggers = $repo->getAll();

		$this->assertArrayEquals( [ 'start-approval-on-edit', 'inactive-trigger' ], array_keys( $triggers ) );
		$this->assertCount( 2, $repo->getAllOfType( 'edit' ) );
	}

	/**
	 * @covers \MediaWiki\Extension\Workflows\TriggerRepo::getActive
	 */
	public function testGetActive() {
		$repo = $this->getRepo();

		$triggers = $repo->getActive( 'edit' );
		$this->assertCount( 1, $triggers );

		$trigger = $triggers[0];

		$this->assertSame( 'start-approval-on-edit', $trigger->getId() );
	}

	/**
	 *
	 * @covers \MediaWiki\Extension\Workflows\TriggerRepo::upsertTrigger
	 * @covers \MediaWiki\Extension\Workflows\TriggerRepo::setContent
	 */
	public function testUpdate() {
		$repo = $this->getRepo();

		$triggers = $repo->getActive( 'edit' );
		$trigger = $triggers[0];
		$data = $trigger->jsonSerialize();
		$this->assertArrayEquals( [
			"username" => "Dummy user",
			"instructions" => "Do this thing!"
		], $data['initData'] );

		$data['initData']['username'] = 'Foo-Bar';
		$updateRes = $repo->upsertTrigger( 'start-approval-on-edit', $data );
		$this->assertTrue( $updateRes );

		$triggers = $repo->getActive( 'edit' );
		$trigger = $triggers[0];
		$this->assertArrayEquals( [
			"username" => "Foo-Bar",
			"instructions" => "Do this thing!"
		], $trigger->jsonSerialize()['initData'] );
	}

	/**
	 * @return TriggerRepo
	 */
	private function getRepo() {
		$workflowFactoryMock = $this->createMock( WorkflowFactory::class );
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$loggerMock = $this->createMock( Logger::class );
		$registry = \ExtensionRegistry::getInstance()->getAttribute( 'WorkflowsTriggerTypes' );

		return new TriggerRepo(
			$workflowFactoryMock, $titleFactory, $loggerMock,
			$objectFactory, 'MediaWiki:WorkflowTriggers', $registry
		);
	}

	/**
	 * @return bool
	 */
	public function needsDB() {
		return true;
	}
}
