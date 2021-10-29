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
//			$t->binary( 'hex' );
//			$t->blob( 'image' );
			$t->bool( 'status' );
			$t->date( 'birthday' );
			$t->datetime( 'ctime' );
			$t->datetimetz( 'mtime' );
			$t->decimal( 'price', 10 );
			$t->float( 'scale' );
			$t->int( 'pos' );
			$t->int( 'test' );
			$t->json( 'config' );
			$t->smallint( 'type' );
			$t->string( 'code' );
			$t->text( 'content' );
			$t->time( 'time' );
			$t->guid( 'uuid' );
			$t->default();

			$t->unique( 'code', 'unq_code' );
			$t->index( ['status', 'pos'], 'idx_status_type' );
			$t->index( 'uuid' );

		} )->up();


		$db->table( 'testref', function( Table $t ) {

			$this->info( 'Create testref table', 1 );

			$t->id();
			$t->foreign( 'parentid', 'test' );
			$t->string( 'label' );

		} )->up();

		$db->insert( 'test', [
//			'hex' => '0xff', 'image' => 'svg+xml:',
			'status' => true, 'birthday' => '2000-01-01',
			'ctime' => '2000-01-01 00:00:00', 'mtime' => '2000-01-01 00:00:00', 'price' => '100.00',
			'scale' => 0.1, 'pos' => 1, 'test' => 1234, 'config' => '{}', 'type' => 123, 'code' => 'test',
			'content' => 'some text', 'time' => '12:00:00', 'uuid' => '7e57d004-2b97-0e7a-b45f-5387367791cd'
		] );

		$db->insert( 'testref', ['parentid' => $db->lastId(), 'label' => 'test ref'] );
	}
}