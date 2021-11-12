<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Delete extends Base
{
	public function up()
	{
		$this->info( 'Removing test tables' );

		$this->dropView( 'testview' )->delete( 'test2' )->dropTable( 'testref2' )->dropTable( 'test2' );
	}
}