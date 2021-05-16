<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Update extends Base
{
	public function up()
	{
		$this->info( 'Change test table', 1 );

		$this->db( 'test' )->table( 'test', function( Table $t ) {

			$t->time( 'time' )->comment( 'some time' );
			$t->text( 'content' )->length( 255 );
			$t->bool( 'status' )->default( true );
			$t->date( 'birthday' )->null( true );
			$t->decimal( 'price', 8 )->scale( 3 );
			$t->int( 'pos' )->type( 'smallint' );
			$t->smallint( 'type' )->unsigned( true );
			$t->string( 'code', 5 )->fixed( true );

		} )->dropColumn( 'test' )->up();

		$rows = $this->db( 'test' )->select( 'test' );

		$expected = [
			'birthday' => ['2000-01-01'],
			'code' => ['test', 'test '], // MySQL/SQLite, PostgreSQL
			'config' => ['{}'],
			'content' => ['some text'],
			'ctime' => ['2000-01-01 00:00:00'],
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
			'uuid' => ['7e57d004-2b97-0e7a-b45f-5387367791cd'],
			'editor' => [null]
		];

		foreach( $expected as $key => $values )
		{
			if( !in_array( $rows[0][$key], $values ) )
			{
				$d1 = var_export( $values, true );
				$d2 = var_export( $rows[0][$key], true );
				throw new \RuntimeException( "Data mismatch for '" . $key . "', expected: " . $d1 . ", actual: " . $d2 );
			}
		}
	}
}