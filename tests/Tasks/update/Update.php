<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Update extends Base
{
	public function up()
	{
		$this->info( 'Change test table', 1 );

		$this->db( 'test' )->table( 'test', function( Table $t ) {

			$t->dropIndex( 'unq_code' )->dropIndex( 'idx_status_type' )->up(); // workaround for SQL Server

			$t->time( 'time' )->comment( 'some time' );
			$t->text( 'content' )->length( 255 );
			$t->bool( 'status' )->default( true );
			$t->date( 'birthday' )->null( true );
			$t->decimal( 'price', 8 )->scale( 3 );
			$t->int( 'pos' )->type( 'smallint' );
			$t->smallint( 'type' )->unsigned( true );
			$t->string( 'code', 5 )->fixed( true );

			$t->unique( 'code', 'unq_code' );
			$t->index( ['status', 'pos'], 'idx_status_type' );
			$t->index( 'uuid' );

		} )->dropColumn( 'test' )->up();

		if( ( $row = current( $this->db( 'test' )->select( 'test', ['id' => 1, 'pos' => 1] ) ) ) === false ) {
			throw new \RuntimeException( 'No row available' );
		}

		$expected = [
			'birthday' => ['2000-01-01'],
			'code' => ['test', 'test '], // MySQL/SQLite, PostgreSQL
			'config' => ['{}'],
			'content' => ['some text'],
			'ctime' => ['2000-01-01 00:00:00', '2000-01-01 00:00:00.000000'], // MySQL5/PostgreSQL/SQLite, SQLServer
//			'hex' => ['0xff'],
			'id' => [1],
//			'image' => ['svg+xml:'],
//			'mtime' => ['2000-01-01 00:00:00', '2000-01-01 00:00:00+xx'], // MySQL5/SQLite, PostgreSQL
			'pos' => [1],
			'price' => [100],
			'scale' => [0.1],
			'status' => [1],
			'time' => ['12:00:00'],
			'type' => [123],
			'uuid' => ['7e57d004-2b97-0e7a-b45f-5387367791cd', '7E57D004-2B97-0E7A-B45F-5387367791CD'], // MySQL5/PostgreSQL/SQLite, SQLServer
			'editor' => [null]
		];

		foreach( $expected as $key => $values )
		{
			if( !in_array( $row[$key], $values ) )
			{
				$d1 = var_export( $values, true );
				$d2 = var_export( $row[$key], true );
				throw new \RuntimeException( "Data mismatch for '" . $key . "', expected: " . $d1 . ", actual: " . $d2 );
			}
		}
	}
}