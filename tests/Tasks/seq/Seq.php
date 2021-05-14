<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Seq extends Base
{
	public function up()
	{
		$this->info( 'Testing sequences' );

		$this->sequence( 'testseq', function( $seq ) {

			$seq->start( 100 )->step( 50 )->cache( 10 );

		} );

		$this->dropSequence( 'testseq' );
	}
}