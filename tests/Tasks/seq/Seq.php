<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Sequence;


class Seq extends Base
{
	public function up()
	{
		$this->info( 'Testing sequences' );

		$this->sequence( 'testseq', function( Sequence $seq ) {

			$seq->start( 100 )->step( 50 )->cache( 10 );

		} );

		$this->dropSequence( 'testseq' );
	}
}