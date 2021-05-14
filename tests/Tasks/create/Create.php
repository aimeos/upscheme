<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Create extends Base
{
	public function up()
	{
		$this->info( 'Create tables' );

		$db = $this->db( 'test' );

		$db->table( 'test', function( Table $t ) {

			$this->info( 'Create test table', 1 );

			$t->bigint( 'id' )->seq( true )->primary( true );
			$t->binary( 'hex' );
			$t->blob( 'image' );
			$t->bool( 'status' );
			$t->date( 'birthday' );
			$t->datetime( 'ctime' );
			$t->datetimetz( 'mtime' );
			$t->decimal( 'price', 10 );
			$t->float( 'scale' );
			$t->int( 'pos' );
			$t->json( 'config' );
			$t->smallint( 'type' );
			$t->string( 'code' );
			$t->text( 'content' );
			$t->time( 'time' );
			$t->uuid( 'uuid' );

			$t->unique( 'code' );
			$t->index( ['status', 'pos'] );

		} );


		$db->table( 'testref', function( Table $t ) {

			$this->info( 'Create testref table', 1 );

			$t->bigint( 'id' )->seq( true )->primary( true );
			$t->foreign( 'parentid', 'test' );
			$t->string( 'label' );

		} );

	}
}