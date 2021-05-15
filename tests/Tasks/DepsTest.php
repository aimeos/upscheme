<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

class DepsTest extends \PHPUnit\Framework\TestCase
{
	private $config;


	protected function setUp() : void
	{
		$this->config = include dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'config.php';
	}


	public function testDeps()
	{
		$this->expectOutputString( 'dep2dep1' );
		\Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/deps' )->up();
	}
}
