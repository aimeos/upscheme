<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Update extends Base
{
	public function up()
	{
		$this->info( 'Change test table', 1 );

		$this->db( 'test' )->table( 'test', function( Table $t ) {

			$t->binary( 'hex' )->comment( 'hex string' );
			$t->blob( 'image' )->length( 255 );
			$t->bool( 'status' )->default( true );
			$t->date( 'birthday' )->null( true );
			$t->decimal( 'price', 8 )->scale( 3 );
			$t->int( 'pos' )->type( 'smallint' );
			$t->smallint( 'type' )->unsigned( true );
			$t->string( 'code' )->fixed( true );

		} )->dropColumn( 'scale' );

	}
}