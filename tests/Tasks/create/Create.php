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

			$this->info( 'Create test table', 'v', 1 );

			$t->bigint( 'id' )->seq( true )->primary();
			// $t->binary( 'hex' ); // PostgreSQL insert problem
			// $t->blob( 'image' ); // PostgreSQL insert problem
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
			// $t->time( 'time' ); // not supported by Oracle
			$t->guid( 'uuid' );
			$t->default();
			$t->uuid( 'uid2' )->null( true )->custom( 'UUID DEFAULT gen_random_uuid() NOT NULL', 'postgresql' );

			$t->unique( 'code', 'unq_code' );
			$t->index( ['status', 'pos'], 'idx_status_type' );
			$t->index( 'uuid' );

		} )->up();


		$db->table( 'testref', function( Table $t ) {

			$this->info( 'Create testref table', 'v', 1 );

			$t->id();
			$t->foreign( 'parentid', 'test' );
			$t->string( 'label' );

		} )->up();


		$this->view( 'testview', 'SELECT ' . $db->qi( 'id' ) . ', ' . $db->qi( 'config' ) . ' FROM ' . $db->qi( 'test' ) );

		if( !$this->hasView( 'testview' ) ) {
			throw new \RuntimeException( 'View not created' );
		}
	}
}