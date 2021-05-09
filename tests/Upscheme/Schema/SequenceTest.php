<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

namespace Aimeos\Upscheme\Schema;


class SequenceTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $seqmock;
	private $dbmock;


	protected function setUp() : void
	{
		$this->dbmock = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\DB' )
			->disableOriginalConstructor()
			->getMock();

		$this->seqmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Sequence' )
			->disableOriginalConstructor()
			->getMock();

		$this->object = new \Aimeos\Upscheme\Schema\Sequence( $this->dbmock, $this->seqmock );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->seqmock, $this->dbmock );
	}


	public function testCall()
	{
		$this->seqmock->expects( $this->once() )->method( 'getAllocationSize' );

		$this->object->getAllocationSize();
	}


	public function testCallMacro()
	{
		\Aimeos\Upscheme\Schema\Sequence::macro( 'unittest', function() { return 'yes'; } );

		$this->assertEquals( 'yes', $this->object->unittest() );
	}


	public function testGetMagic()
	{
		$object = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Sequence' )
			->disableOriginalConstructor()
			->setMethods( ['cache'] )
			->getMock();

		$object->expects( $this->once() )->method( 'cache' )
			->will( $this->returnValue( false ) );

		$this->assertEquals( false, $object->cache );
	}


	public function testSetMagic()
	{
		$object = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Sequence' )
			->disableOriginalConstructor()
			->setMethods( ['cache'] )
			->getMock();

		$object->expects( $this->once() )->method( 'cache' );

		$object->cache = true;
	}


	public function testName()
	{
		$this->seqmock->expects( $this->once() )->method( 'getName' )->will( $this->returnValue( 'seq_name' ) );

		$this->assertEquals( 'seq_name', $this->object->name() );
	}


	public function testCacheGet()
	{
		$this->assertEquals( false, $this->object->cache() );
	}


	public function testCacheSet()
	{
		$this->seqmock->expects( $this->once() )->method( 'setCache' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Sequence::class, $this->object->cache( true ) );
	}


	public function testStartGet()
	{
		$this->seqmock->expects( $this->once() )->method( 'getInitialValue' )->will( $this->returnValue( 1 ) );

		$this->assertEquals( 1, $this->object->start() );
	}


	public function testStartSet()
	{
		$this->seqmock->expects( $this->once() )->method( 'setInitialValue' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Sequence::class, $this->object->start( 10 ) );
	}


	public function testStepGet()
	{
		$this->seqmock->expects( $this->once() )->method( 'getAllocationSize' )->will( $this->returnValue( 1 ) );

		$this->assertEquals( 1, $this->object->step() );
	}


	public function testStepSet()
	{
		$this->seqmock->expects( $this->once() )->method( 'setAllocationSize' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Sequence::class, $this->object->step( 2 ) );
	}


	public function testUp()
	{
		$this->dbmock->expects( $this->once() )->method( 'up' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Sequence::class, $this->object->up() );
	}
}
