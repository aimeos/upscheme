<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

namespace Aimeos\Upscheme\Up;


class UpTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		$this->object = $this->getMockBuilder( '\Aimeos\Upscheme\Up' )
			->setConstructorArgs( [['driver' => 'sqlite'], 'test'] )
			->setMethods( ['test'] )
			->getMock();
	}


	protected function tearDown() : void
	{
		unset( $this->object );
	}


	public function testConstruct()
	{
		$object = new \Aimeos\Upscheme\Up( ['driver' => ''], 'test' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $object );
	}


	public function testConstructConfig()
	{
		$this->expectException( '\RuntimeException' );
		new \Aimeos\Upscheme\Up( [], 'test' );
	}


	public function testConstructPath()
	{
		$this->expectException( '\RuntimeException' );
		new \Aimeos\Upscheme\Up( ['driver' => ''], [] );
	}


	public function testAutoload()
	{
		$object = new \Aimeos\Upscheme\Up( ['driver' => 'pdo_sqlite'], dirname( __DIR__ ) . '/Tasks/test' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $object->up() );
	}


	public function testAutoloadCustom()
	{
		\Aimeos\Upscheme\Up::macro( 'autoload', function( $class ) { return true; } );

		$object = new \Aimeos\Upscheme\Up( ['driver' => 'pdo_sqlite'], dirname( __DIR__ ) . '/Tasks/test' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $object->up() );
	}


	public function testDb()
	{
		$object = new \Aimeos\Upscheme\Up( ['test' => ['driver' => 'pdo_sqlite', 'path' => 'up.test']], 'testpath' );

		$db = $object->db( 'test' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $db );

		$db2 = $object->db( 'test' );
		$this->assertSame( $db, $db2 );
	}


	public function testDbNew()
	{
		$object = new \Aimeos\Upscheme\Up( ['test' => ['driver' => 'pdo_sqlite', 'path' => 'up.test']], 'testpath' );

		$db = $object->db( 'test' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $db );

		$db2 = $object->db( 'test', true );
		$this->assertNotSame( $db, $db2 );
	}


	public function testDbFallback()
	{
		$object = new \Aimeos\Upscheme\Up( ['test' => ['driver' => 'pdo_sqlite', 'path' => 'up.test']], 'testpath' );

		$db = $object->db( 'test2' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $db );

		$db2 = $object->db();
		$this->assertSame( $db, $db2 );
	}


	public function testDbSingle()
	{
		$object = new \Aimeos\Upscheme\Up( ['driver' => 'pdo_sqlite', 'path' => 'up.test'], 'testpath' );

		$db = $object->db( 'test2' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $db );

		$db2 = $object->db();
		$this->assertSame( $db, $db2 );
	}


	public function testDbCustom()
	{
		\Aimeos\Upscheme\Up::macro( 'connect', function( array $cfg ) {
			return \Doctrine\DBAL\DriverManager::getConnection( ['driver' => 'pdo_sqlite', 'path' => 'up.test'] );
		} );

		$result = ( new \Aimeos\Upscheme\Up( ['driver' => 'pdo_mysql'], 'testpath' ) )->db();
		\Aimeos\Upscheme\Up::reset( 'connect' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $result );
	}


	public function testInfo()
	{
		$this->expectOutputString( 'test' . PHP_EOL );
		$this->object->info( 'test', '' );
	}


	public function testInfoVerbose()
	{
		$this->expectOutputString( '' );
		$this->object->info( 'test', 'v' );
	}


	public function testInfoCustom()
	{
		\Aimeos\Upscheme\Up::macro( 'info', function( $msg ) { echo 'custom'; } );

		$this->expectOutputString( 'custom' );
		$this->object->info( 'test', 'v' );

		\Aimeos\Upscheme\Up::reset( 'info' );
	}


	public function testPaths()
	{
		$this->assertEquals( ['test'], $this->object->paths() );
	}


	public function testUse()
	{
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, \Aimeos\Upscheme\Up::use( ['driver' => ''], 'test' ) );
	}


	public function testVerbose()
	{
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $this->object->verbose() );
	}


	public function testVerboseMore()
	{
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $this->object->verbose( 'vv' ) );
	}


	public function testVerboseCustom()
	{
		\Aimeos\Upscheme\Up::macro( 'verbose', function( $level ) { return 3; } );
		$result = $this->object->verbose( 'v' );
		\Aimeos\Upscheme\Up::reset( 'info' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $result );
	}
}
