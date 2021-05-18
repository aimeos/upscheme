<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Conn extends Base
{
	public function up()
	{
		$this->info( 'Testing connections' );

		$this->db( 'test' )->table( 'testconn' )->int( 'id' )->up();

		for( $i = 0; $i < 250; $i++ ) {
			$this->db( 'test', true )->delete( 'testconn' )->close();
		}

		$this->db( 'test' )->dropTable( 'testconn' )->up();
	}
}