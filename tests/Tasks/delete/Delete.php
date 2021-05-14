<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Delete extends Base
{
	public function up()
	{
		$this->info( 'Removing test tables' );

		$this->delete( 'test' )->dropTable( 'testref' )->dropTable( 'test' );
	}
}