<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Rename extends Base
{
	public function up()
	{
		$this->info( 'Renames test tables/columns/indexes' );

		$this->renameTable( ['test' => 'test2', 'testref' => 'testref2'] );

		if( !$this->hasTable( ['test2', 'testref2'] ) ) {
			throw new \Exception( 'Renaming tables failed' );
		}

		$this->renameColumn( 'test2', ['uuid' => 'guid'] );

		if( !$this->hasColumn( 'test2', 'guid' ) ) {
			throw new \Exception( 'Renaming column failed' );
		}

		$this->renameIndex( 'test2', ['unq_code' => 'unq_code2'] );

		if( !$this->hasIndex( 'test2', 'unq_code2' ) ) {
			throw new \Exception( 'Renaming index failed' );
		}
	}
}