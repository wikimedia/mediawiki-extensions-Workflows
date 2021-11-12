<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Definition\DefinitionSource;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionSource
 */
class DefinitionSourceTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionSource::getParams()
	 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionSource::getVersion()
	 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionSource::getRepositoryKey()
	 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionSource::getName()
	 */
	public function testSource() {
		$source = new DefinitionSource(
			'dummyRepo', 'dummyDef',
			3, [ 'param1' => 'param_value' ]
		);

		$this->assertEquals(
			'dummyDef', $source->getName(), 'Name of the definition should be retrieved correctly'
		);
		$this->assertEquals(
			'dummyRepo', $source->getRepositoryKey(),
			'Name of the repository should be retrieved correctly'
		);
		$this->assertEquals( 3, $source->getVersion(), 'Version should be retrieved correctly' );
		$this->assertEquals(
			[ 'param1' => 'param_value' ], $source->getParams(),
			'Params should be retrieved correctly'
		);
	}
}
