<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

namespace Aimeos\Upscheme\Schema;


class ForeignTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $dbalmock;
	private $tablemock;


	protected function setUp() : void
	{
		$this->tablemock = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->dbalmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->object = new \Aimeos\Upscheme\Schema\Foreign( $this->tablemock, $this->dbalmock, ['local'], 'fktable', ['foreign'], 'fk_name' );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->dbalmock, $this->tablemock );
	}


	public function testCallMacro()
	{
		\Aimeos\Upscheme\Schema\Foreign::macro( 'unittest', function() { return 'yes'; } );

		$this->assertEquals( 'yes', $this->object->unittest() );
	}


	public function testCall()
	{
		$this->expectException( '\BadMethodCallException' );
		$this->object->unittest2();
	}


	public function testGetMagic()
	{
		$object = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Foreign' )
			->disableOriginalConstructor()
			->setMethods( ['onDelete'] )
			->getMock();

		$object->expects( $this->once() )->method( 'onDelete' )
			->will( $this->returnValue( 'CASCADE' ) );

		$this->assertEquals( 'CASCADE', $object->onDelete );
	}


	public function testSetMagic()
	{
		$object = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Foreign' )
			->disableOriginalConstructor()
			->setMethods( ['onDelete'] )
			->getMock();

			$object->expects( $this->once() )->method( 'onDelete' );

		$object->onDelete = 'RESTRICT';
	}


	public function testDo()
	{
		$this->dbalmock->expects( $this->once() )->method( 'removeForeignKey' );
		$this->dbalmock->expects( $this->once() )->method( 'addForeignKeyConstraint' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Foreign::class, $this->object->do( 'CASCADE' ) );
		$this->assertEquals( 'CASCADE', $this->object->onDelete );
		$this->assertEquals( 'CASCADE', $this->object->onUpdate );
	}


	public function testName()
	{
		$this->assertEquals( 'fk_name', $this->object->name() );
	}


	public function testOnDeleteGet()
	{
		$this->assertEquals( 'CASCADE', $this->object->onDelete() );
	}


	public function testOnDeleteSet()
	{
		$this->dbalmock->expects( $this->once() )->method( 'removeForeignKey' );
		$this->dbalmock->expects( $this->once() )->method( 'addForeignKeyConstraint' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Foreign::class, $this->object->onDelete( 'RESTRICT' ) );
	}


	public function testOnUpdateGet()
	{
		$this->assertEquals( 'CASCADE', $this->object->onUpdate() );
	}


	public function testOnUpdateSet()
	{
		$this->dbalmock->expects( $this->once() )->method( 'removeForeignKey' );
		$this->dbalmock->expects( $this->once() )->method( 'addForeignKeyConstraint' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Foreign::class, $this->object->onUpdate( 'RESTRICT' ) );
	}


	public function testUp()
	{
		$this->tablemock->expects( $this->once() )->method( 'up' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Foreign::class, $this->object->up() );
	}
}
