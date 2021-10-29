<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

namespace Aimeos\Upscheme\Task;


class BaseTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $upmock;
	private $dbmock;


	protected function setUp() : void
	{
		$this->dbmock = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\DB' )
			->disableOriginalConstructor()
			->getMock();

		$this->upmock = $this->getMockBuilder( '\Aimeos\Upscheme\Up' )
			->disableOriginalConstructor()
			->getMock();

		$this->object = $this->getMockBuilder( '\Aimeos\Upscheme\Task\Base' )
			->setConstructorArgs( [$this->upmock] )
			->setMethods( ['test'] )
			->getMockForAbstractClass();
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->dbmock, $this->upmock );
	}


	public function testCall()
	{
		$this->upmock->expects( $this->once() )->method( 'db' )->will( $this->returnValue( $this->dbmock ) );
		$this->dbmock->expects( $this->once() )->method( 'dropTable' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropTable( 'unittest' ) );
	}


	public function testCallMacro()
	{
		\Aimeos\Upscheme\Task\Base::macro( 'unittest', function() { return 'yes'; } );

		$this->assertEquals( 'yes', $this->object->unittest() );
	}


	public function testAfter()
	{
		$this->assertEquals( [], $this->object->after() );
	}


	public function testBefore()
	{
		$this->assertEquals( [], $this->object->before() );
	}


	public function testDb()
	{
		$this->upmock->expects( $this->once() )->method( 'db' )->will( $this->returnValue( $this->dbmock ) );

		$result = $this->access( 'db' )->invokeArgs( $this->object, ['unittest', true] );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $result );
	}


	public function testInfo()
	{
		$this->upmock->expects( $this->once() )->method( 'info' );

		$result = $this->access( 'info' )->invokeArgs( $this->object, ['unittest', 2] );

		$this->assertInstanceOf( \Aimeos\Upscheme\Task\Iface::class, $result );
	}


	public function testPaths()
	{
		$this->upmock->expects( $this->once() )->method( 'paths' )->will( $this->returnValue( [dirname( __DIR__, 2 ) . '/Tasks'] ) );

		$result = $this->access( 'paths' )->invokeArgs( $this->object, ['test'] );

		$this->assertEquals( '/test', substr( current( $result ), -5 ) );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Upscheme\Task\Base::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
