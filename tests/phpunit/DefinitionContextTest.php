<?php

namespace MediaWiki\Extension\Workflows\Tests;

use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionContext
 */
class DefinitionContextTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionContext::getItemKeys()
	 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionContext::getItem()
	 * @covers \MediaWiki\Extension\Workflows\Definition\DefinitionContext::convertData()
	 */
	public function testContext() {
		$data = [
			'stringValue' => 'value',
			'intValue' => '298',
			'floatValue' => '30.35',
			'boolValue' => 'False',
			'boolValue2' => 'true'
		];
		$context = new DefinitionContext( $data );

		$this->assertCount(
			count( $data ), $context->getItemKeys(),
			'Context must have the same number of keys as it is passed'
		);

		$this->assertIsFloat( $context->getItem( 'floatValue' ), 'Retrieved value must be float' );
		$this->assertEquals( 30.35, $context->getItem( 'floatValue' ), 'Retrieved value must be float' );
		$this->assertIsInt( $context->getItem( 'intValue' ), 'Retrieved value must be int' );
		$this->assertEquals( 298, $context->getItem( 'intValue' ), 'Retrieved value must be int' );
		$this->assertIsString( $context->getItem( 'stringValue' ), 'Retrieved value must be string' );
		$this->assertIsBool( $context->getItem( 'boolValue' ), 'Retrieved value must be bool' );
		$this->assertIsBool( $context->getItem( 'boolValue2' ), 'Retrieved value must be bool' );
	}
}
