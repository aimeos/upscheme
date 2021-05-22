<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

namespace Aimeos\Upscheme\Up;


class MacroTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		$this->object = $this->getMockBuilder( '\Aimeos\Upscheme\Macro' )
			->getMockForTrait();
	}


	protected function tearDown() : void
	{
		unset( $this->object );
	}


	public function testMacro()
	{
		$this->object::macro( 'macrotest', function( $a, $b ) { return $a + $b; } );
		$this->assertEquals( 2, $this->object->macrotest( 1, 1 ) );
	}


	public function testMacroNone()
	{
		$this->assertNull( $this->object::macro( 'none' ) );
	}


	public function testMacroReset()
	{
		$this->object::macro( 'macrotest', function( $a, $b ) { return $a + $b; } );
		$this->object::reset( 'macrotest' );

		$this->assertNull( $this->object::macro( 'macrotest' ) );
	}


	public function testMacroResetAll()
	{
		$this->object::macro( 'macrotest', function( $a, $b ) { return $a + $b; } );
		$this->object::reset();

		$this->assertNull( $this->object::macro( 'macrotest' ) );
	}


	public function testCall()
	{
		$this->object::macro( 'macrotest', function() { return true; } );
		$this->assertTrue( $this->object->call( 'macrotest', [] ) );
	}


	public function testCallInvalid()
	{
		$this->expectException( '\BadMethodCallException' );
		$this->object->call( 'macrotest2', [] );
	}


	public function testCallMagic()
	{
		$this->expectException( '\BadMethodCallException' );
		$this->object->macrotest2();
	}
}
