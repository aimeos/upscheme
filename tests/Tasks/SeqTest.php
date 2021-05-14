<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

class SeqTest extends \PHPUnit\Framework\TestCase
{
	private $config;


	protected function setUp() : void
	{
		$this->config = include dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'config.php';
	}


	public function testSeq()
	{
		$result = \Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/seq' )->up();
		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $result );
	}
}
