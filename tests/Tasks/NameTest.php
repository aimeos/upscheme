<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

class NameTest extends \PHPUnit\Framework\TestCase
{
	private $config;


	protected function setUp() : void
	{
		$this->config = include dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'config.php';


		Aimeos\Upscheme\Schema\Table::macro( 'nameIndex', function( string $table, array $columns, string $type ) {

			$parts = explode( '_', $table );

			$table = substr( (string) array_shift( $parts ), 0, 2 );
			$table .= substr( (string) array_shift( $parts ), 0, 3 );

			foreach( $parts as $part ) {
				$table .= substr( $part, 0, 2 );
			}

			$max = 30 - strlen( $table ) - strlen( $type ) - count( $columns ) - 1;
			$count = count( $columns );

			foreach( $columns as $idx => $name )
			{
				$num = floor( $max / $count-- );
				$parts = explode( '_', $name );
				$name = array_pop( $parts );

				if( !substr_compare( $name, 'id', -2, 2 ) && strlen( $name ) > 2 ) {
					$name = $name[0] . 'id';
				}

				foreach( array_reverse( $parts ) as $part ) {
					$name = $part[0] . $name;
				}

				$columns[$idx] = substr( $name, 0, $num );
				$max -= strlen( $columns[$idx] );
			}

			return $type . '_' . $table . '_' . join( '_', $columns );
		} );
	}


	public function testName()
	{
		$up = \Aimeos\Upscheme\Up::use( $this->config, __DIR__ . '/name' )->up();

		$this->assertInstanceOf( \Aimeos\Upscheme\Up::class, $up );
		$this->assertTrue( $up->db()->hasIndex( 'testname', 'unq_te_sid_pid' ) );
		$this->assertTrue( $up->db()->hasIndex( 'testname', 'idx_te_sid_pid_obpid' ) );
		$this->assertTrue( $up->db()->hasIndex( 'testname', 'idx_te_sid_pnam_obpn_obpt_obpv' ) );

		$up->db()->dropTable( 'testname' );
	}
}
