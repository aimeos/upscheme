<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Name extends Base
{
	public function up()
	{
		$this->info( 'Testing Names' );

		$this->table( 'testname', function( $table ) {

			$table->string( 'id' );
			$table->string( 'siteid', 32 );
			$table->string( 'product_id', 32 );
			$table->string( 'order_base_product_id', 32 );
			$table->string( 'product_name', 32 );
			$table->string( 'order_base_product_name' );
			$table->string( 'order_base_product_type', 32 );
			$table->string( 'order_base_product_value' );

			$table->primary( 'id' );
			$table->unique( ['siteid', 'product_id'] );
			$table->index( ['siteid', 'product_id', 'order_base_product_id'] );
			$table->index( ['siteid', 'product_name', 'order_base_product_name', 'order_base_product_type', 'order_base_product_value'] );

		} );
	}
}