<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024
 */


namespace Aimeos\Upscheme;


/**
 * Database migration file generator
 */
class Generate
{
	private string $path;
	private string $seqtpl;
	private string $tabletpl;
	private string $viewtpl;


	/**
	 * Initializes the object
	 *
	 * @param string $path Path where the migration files should be stored
	 */
	public function __construct( string $path )
	{
		$dir = dirname( __DIR__ );
		$ds = DIRECTORY_SEPARATOR;

		$this->seqtpl = $this->read( $dir . $ds . 'stubs' . $ds . 'sequence.stub' );
		$this->tabletpl = $this->read( $dir . $ds . 'stubs' . $ds . 'table.stub' );
		$this->viewtpl = $this->read( $dir . $ds . 'stubs' . $ds . 'view.stub' );

		$this->path = $path;
	}


	/**
	 * Generates the migration files
	 *
	 * @param array $schema Associative list of schema definitions
	 * @param string $dbname Name of the database
	 * @throws \RuntimeException If a file can't be created
	 */
	public function __invoke( array $schema, string $dbname = '' ) : void
	{
		$ds = DIRECTORY_SEPARATOR;
		$prefix = $dbname ? preg_replace( '/[^A-Za-z0-9]/', '', $dbname ) . '_' : '';

		$seqtpl = str_replace( '{{DB}}', $dbname, $this->seqtpl );
		$tabletpl = str_replace( '{{DB}}', $dbname, $this->tabletpl );
		$viewtpl = str_replace( '{{DB}}', $dbname, $this->viewtpl );

		foreach( $schema['sequence'] ?? [] as $name => $def ) {
			$this->write( $this->path . $ds . $prefix . 'seq_' . $name . '.php', $this->sequence( $def, $seqtpl ) );
		}

		foreach( $schema['table'] ?? [] as $name => $def ) {
			$this->write( $this->path . $ds . $prefix . 'table_' . $name . '.php', $this->table( $def, $tabletpl ) );
		}

		foreach( $schema['view'] ?? [] as $name => $def ) {
			$this->write( $this->path . $ds . $prefix . 'view_' . $name . '.php', $this->view( $def, $viewtpl ) );
		}
	}


	/**
	 * Returns the PHP code for an array
	 *
	 * @param array $a Associative list of array values
	 * @return string PHP code for the array
	 */
	protected function array( array $a ) : string
	{
		return str_replace( ['array (', ')', "\n", ' ', ',]'], ['[', ']', '', '', ']'], var_export( $a, true ) );
	}


	/**
	 * Returns the PHP code for a column definition
	 *
	 * @param array $def Associative list of column definitions
	 * @return string PHP code for the column definitions
	 */
	protected function col( array $def ) : string
	{
		$lines = [];
		$types = [
			'bigint', 'binary', 'blob', 'boolean',
			'date', 'datetime', 'datetimetz',
			'float', 'guid', 'integer', 'json',
			'smallint', 'string', 'text', 'time'
		];

		foreach( $def as $name => $e )
		{
			if( in_array( $e['type'], $types ) ) {
				$string = '$t->' . $e['type'] . '( \'' . $name . '\' )';
			} else {
				$string = '$t->col( \'' . $name . '\', \'' . $e['type'] . '\' )';
			}

			foreach( ['seq', 'fixed', 'unsigned', 'null'] as $key )
			{
				if( $e[$key] ?? false ) {
					$string .= '->' . $key . '( true )';
				}
			}

			foreach( ['length', 'precision', 'scale'] as $key )
			{
				if( $e[$key] ?? false ) {
					$string .= '->' . $key . '( ' . $e[$key] . ' )';
				}
			}

			foreach( ['default', 'comment'] as $key )
			{
				if( $e[$key] ?? false ) {
					$string .= '->' . $key . '( \'' . $e[$key] . '\' )';
				}
			}

			foreach( $e['opt'] ?? [] as $key => $value ) {
				$string .= '->opt( \'' . $key . '\', \'' . $value . '\' )';
			}

			$lines[] = $string . ';';
		}

		return join( "\n\t\t\t", $lines );
	}


	/**
	 * Returns the PHP code for a foreign key definition
	 *
	 * @param array $def Associative list of foreign key definitions
	 * @return string PHP code for the foreign key definitions
	 */
	protected function foreign( array $def ) : string
	{
		$lines = [];

		foreach( $def as $name => $e )
		{
			$string = '$t->foreign( ' . json_encode( $e['localcol'] ) . ', \'' . ( $e['fktable'] ?? '' ) . '\', ' . json_encode( $e['fkcol'] ) . ', ' . ( $e['name'] ? '\'' . $e['name'] . '\'' : 'null' ) . ' )';

			foreach( ['onDelete', 'onUpdate'] as $key )
			{
				if( $e[$key] ?? false ) {
					$string .= '->' . $key . '( \'' . $e[$key] . '\' )';
				}
			}

			$lines[] = $string . ';';
		}

		return join( "\n\t\t\t", $lines );
	}


	/**
	 * Returns the PHP code for an index definition
	 *
	 * @param array $def Associative list of index definitions
	 * @return string PHP code for the index definitions
	 */
	protected function index( array $def ) : string
	{
		$lines = [];

		foreach( $def as $name => $e )
		{
			if( $e['primary'] ?? false ) {
				$lines[] = '$t->primary( ' . json_encode( $e['columns'] ) . ', ' . ( $e['name'] ? '\'' . $e['name'] . '\'' : 'null' ) . ' );';
			} elseif( $e['unique'] ?? false ) {
				$lines[] = '$t->unique( ' . json_encode( $e['columns'] ) . ', ' . ( $e['name'] ? '\'' . $e['name'] . '\'' : 'null' ) . ' );';
			} else {
				$lines[] = '$t->index( ' . json_encode( $e['columns'] ?? [] ) . ', ' . ( $e['name'] ? '\'' . $e['name'] . '\'' : 'null' ) . ', ' . $this->array( $e['flags'] ?? [] ) . ', ' . $this->array( $e['options'] ?? [] ) . ' );';
			}
		}

		return join( "\n\t\t\t", $lines );
	}


	/**
	 * Reads the content of a file
	 *
	 * @param string $filename Name of the file
	 * @return string Content of the file
	 * @throws \RuntimeException If the file can't be read
	 */
	protected function read( string $filename ) : string
	{
		if( ( $content = file_get_contents( $filename ) ) === false ) {
			throw new \RuntimeException( 'Unable to read from file "' . $filename . '"' );
		}

		return $content;
	}


	/**
	 * Returns the PHP code for a sequence definition
	 *
	 * @param array $def Associative list of sequence definitions
	 * @param string $template Template for the sequence definition
	 * @return string PHP code for the sequence definitions
	 */
	protected function sequence( array $def, string $template ) : string
	{
		$string = '';

		foreach( ['cache', 'start', 'step'] as $key )
		{
			if( $def[$key] ?? false ) {
				$string .= '->' . $key . '( ' . $def[$key] . ' )';
			}
		}

		if( $string ) {
			$string = '$s' . $string . ';';
		}

		return str_replace( ['{{NAME}}', '{{SEQUENCE}}'], [$def['name'] ?? '', $string], $template );
	}


	/**
	 * Returns the PHP code for a table definition
	 *
	 * @param array $def Associative list of table definitions
	 * @param string $template Template for the table definition
	 * @return string PHP code for the table definitions
	 */
	protected function table( array $def, string $template ) : string
	{
		$fktables = [];

		foreach( $def['foreign'] ?? [] as $e ) {
			$fktables[] = 'table_' . $e['fktable'] ?? '';
		}

		return str_replace( [
			'{{NAME}}',
			'{{COLUMN}}',
			'{{INDEX}}',
			'{{FOREIGN}}',
			'{{AFTER}}'
		], [
			$def['name'] ?? '',
			$this->col( $def['col'] ?? [] ),
			$this->index( $def['index'] ?? [] ),
			$this->foreign( $def['foreign'] ?? [] ),
			$fktables ? "'" . join( "', '", $fktables ) . "'" : ''
		], $template );
	}


	/**
	 * Returns the PHP code for a view definition
	 *
	 * @param array $def Associative list of view definitions
	 * @param string $template Template for the view definition
	 * @return string PHP code for the view definitions
	 */
	protected function view( array $def, string $template ) : string
	{
		return str_replace( ['{{NAME}}', '{{SQL}}'], [$def['name'] ?? '', $def['sql'] ?? ''], $template );
	}


	/**
	 * Writes the content to the file
	 *
	 * @param string $filename Name of the file
	 * @param string $content Content to write
	 * @throws \RuntimeException If the file can't be written
	 */
	protected function write( string $filename, string $content ) : void
	{
		if( file_put_contents( $filename, $content ) === false ) {
			throw new \RuntimeException( 'Unable to write to file "' . $filename . '"' );
		}
	}
}
