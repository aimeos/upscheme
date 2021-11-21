<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Insert extends Base
{
	public function up()
	{
		$this->info( 'Insert data' );

		$db = $this->db( 'test' );

		$db->insert( 'test', [
//			'hex' => '0xff', 'image' => 'svg+xml:',
			'status' => true, 'birthday' => '2000-01-01',
			'ctime' => '2000-01-01 00:00:00', 'mtime' => '2000-01-01 00:00:00', 'price' => '100.00',
			'scale' => 0.1, 'pos' => 1, 'test' => 1234, 'config' => '{}', 'type' => 123, 'code' => 'test',
			'content' => 'some text', 'uuid' => '7e57d004-2b97-0e7a-b45f-5387367791cd'
		] );

		$seq = $db->type() === 'postgresql' ? 'test_id_seq' : ( $db->type() === 'oracle' ? 'test_SEQ' : null );
		$db->insert( 'testref', ['parentid' => $db->lastId( $seq ), 'label' => 'test ref'] );
	}
}