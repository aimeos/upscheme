<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

class ConnTest extends \PHPUnit\Framework\TestCase
{
	private $config;


	protected function setUp() : void
	{
		$this->config = include dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'config.php';
	}


	public function testConn()
	{
		$result = \Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/conn' )->up();
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $result );
	}
}
