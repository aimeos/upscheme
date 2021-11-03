<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

class CrudTest extends \PHPUnit\Framework\TestCase
{
	private $config;


	protected function setUp() : void
	{
		$this->config = include dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'config.php';
	}


	public function testCreate()
	{
		Aimeos\Upscheme\Schema\Table::macro( 'default', function() {
			$this->opt( 'engine', 'InnoDB' );
			$this->string( 'editor' )->null( true );
		} );

		$result = \Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/create' )->up();
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $result );
	}


	public function testUpdate()
	{
		$result = \Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/update' )->up();
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $result );
	}


	public function testRename()
	{
		$result = \Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/rename' )->up();
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $result );
	}


	public function testDelete()
	{
		$result = \Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/delete' )->up();
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $result );
	}
}
