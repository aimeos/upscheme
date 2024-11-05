<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024
 */


class UpTest extends \PHPUnit\Framework\TestCase
{
	private $config;
	private $dir;


	protected function setUp() : void
	{
		Aimeos\Upscheme\Schema\Table::macro( 'default', function() {
			$this->opt( 'engine', 'InnoDB' );
			$this->string( 'editor' )->null( true );
		} );

		$this->config = include dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'config.php';
		$this->dir = dirname( __DIR__ ) . '/tmp';
	}


	public function testCreate()
	{
		file_exists( $this->dir ) ?: mkdir( $this->dir );

		$object = \Aimeos\Upscheme\Up::use( $this->config, dirname( __DIR__ ) . '/Tasks/create' )->up();

		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $object );
	}


	public function testExecute()
	{
		$object = \Aimeos\Upscheme\Up::use( $this->config, $this->dir )->create();
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $object );

		\Aimeos\Upscheme\Up::use( $this->config, $this->dir )->up();
	}


	public function testCleanup()
	{
		$object = \Aimeos\Upscheme\Up::use( $this->config, dirname( __DIR__ ) . '/Tasks/rename' )->up();
		$object2 = \Aimeos\Upscheme\Up::use( $this->config, dirname( __DIR__ ) . '/Tasks/delete' )->up();

		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $object );
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $object2 );
	}
}
