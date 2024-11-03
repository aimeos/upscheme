<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024
 */

class AnonTest extends \PHPUnit\Framework\TestCase
{
	private $config;


	protected function setUp() : void
	{
		$this->config = include dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'config.php';
	}


	public function testDeps()
	{
		$this->expectOutputString( 'anonymous class' );
		\Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/anon' )->up();
	}
}
