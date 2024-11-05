<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024
 */


class UpTest extends \PHPUnit\Framework\TestCase
{
	private $config;


	protected function setUp() : void
	{
		Aimeos\Upscheme\Schema\Table::macro( 'default', function() {
			$this->opt( 'engine', 'InnoDB' );
			$this->string( 'editor' )->null( true );
		} );

		$this->config = include dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'config.php';
	}


	public function testCreate()
	{
		$dir = dirname( __DIR__ ) . '/tmp';
		file_exists( $dir ) ?: mkdir( $dir );

		\Aimeos\Upscheme\Up::use( $this->config, dirname( __DIR__ ) . '/Tasks/create' )->up();

		$object = \Aimeos\Upscheme\Up::use( $this->config, $dir )->create();
		\Aimeos\Upscheme\Up::use( $this->config, $dir )->up();

		\Aimeos\Upscheme\Up::use( $this->config, dirname( __DIR__ ) . '/Tasks/rename' )->up();
		\Aimeos\Upscheme\Up::use( $this->config, dirname( __DIR__ ) . '/Tasks/delete' )->up();

		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $object );
	}
}
