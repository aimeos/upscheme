<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Upscheme\Schema;


/**
 * Database schema manager class
 */
class DB
{
	use \Aimeos\Upscheme\Macro;


	private $conn;
	private $from;
	private $to;
	private $up;


	/**
	 * Initializes the database schema manager object
	 *
	 * @param \Aimeos\Upscheme\Up $up Main Upscheme object
	 * @param \Doctrine\DBAL\Connection $conn Doctrine database connection
	 */
	public function __construct( \Aimeos\Upscheme\Up $up, \Doctrine\DBAL\Connection $conn )
	{
		$this->to = $conn->createSchemaManager()->createSchema();
		$this->from = clone $this->to;

		$this->conn = $conn;
		$this->up = $up;
	}


	/**
	 * Calls custom methods or passes unknown method calls to the Doctrine schema object
	 *
	 * @param string $method Name of the method
	 * @param array $args Method parameters
	 * @return mixed Return value of the called method
	 */
	public function __call( string $method, array $args )
	{
		if( $fcn = self::macro( $method ) ) {
			return $this->call( $method, $args );
		}

		return $this->to->{$method}( ...$args );
	}


	/**
	 * Clones the internal objects
	 */
	public function __clone()
	{
		$this->up();

		$this->to = clone $this->to;
		$this->conn = clone $this->conn;

		$this->conn->close();
	}


	/**
	 * Closes the database connection
	 */
	public function close()
	{
		$this->conn->close();
	}


	/**
	 * Deletes the records from the given table
	 *
	 * Warning: The condition values are escaped but the table name and condition
	 * column names are not! Only use fixed strings for table name and condition
	 * column names but no external input!
	 *
	 * @param string $table Name of the table
	 * @param array|null $conditions Key/value pairs of column names and value to compare with
	 * @return self Same object for fluid method calls
	 */
	public function delete( string $table, array $conditions = null ) : self
	{
		$this->conn->delete( $table, $conditions ?? [1 => 1] );
		return $this;
	}


	/**
	 * Drops the column given by its name if it exists
	 *
	 * @param string $table Name of the table the column belongs to
	 * @param array|string $name Name of the column or columns
	 * @return self Same object for fluid method calls
	 */
	public function dropColumn( string $table, $name ) : self
	{
		foreach( (array) $name as $entry )
		{
			if( $this->hasColumn( $table, $entry ) ) {
				$this->table( $table )->dropColumn( $entry );
			}
		}

		return $this->up();
	}


	/**
	 * Drops the foreign key constraint given by its name if it exists
	 *
	 * @param string $table Name of the table the foreign key constraint belongs to
	 * @param array|string $name Name of the foreign key constraint or constraints
	 * @return self Same object for fluid method calls
	 */
	public function dropForeign( string $table, $name ) : self
	{
		foreach( (array) $name as $entry )
		{
			if( $this->hasForeign( $table, $entry ) ) {
				$this->table( $table )->dropForeign( $entry );
			}
		}

		return $this->up();
	}


	/**
	 * Drops the index given by its name if it exists
	 *
	 * @param string $table Name of the table the index belongs to
	 * @param array|string $name Name of the index or indexes
	 * @return self Same object for fluid method calls
	 */
	public function dropIndex( string $table, $name ) : self
	{
		foreach( (array) $name as $entry )
		{
			if( $this->hasIndex( $table, $entry ) ) {
				$this->table( $table )->dropIndex( $entry );
			}
		}

		return $this->up();
	}


	/**
	 * Drops the sequence given by its name if it exists
	 *
	 * @param array|string $name Name of the sequence or sequences
	 * @return self Same object for fluid method calls
	 */
	public function dropSequence( $name ) : self
	{
		foreach( (array) $name as $entry )
		{
			if( $this->hasSequence( $entry ) ) {
				$this->to->dropSequence( $entry );
			}
		}

		return $this->up();
	}


	/**
	 * Drops the table given by its name if it exists
	 *
	 * @param array|string $name Name of the table or tables
	 * @return self Same object for fluid method calls
	 */
	public function dropTable( $name ) : self
	{
		foreach( (array) $name as $entry )
		{
			if( $this->hasTable( $entry ) ) {
				$this->to->dropTable( $entry );
			}
		}

		return $this->up();
	}


	/**
	 * Executes a custom SQL statement if the database is of the given type
	 *
	 * The database changes are not applied immediately so always call up()
	 * before executing custom statements to make sure that the tables you want
	 * to use has been created before!
	 *
	 * @param string $type Database type
	 * @param array|string $sql Custom SQL statement or statements
	 * @return self Same object for fluid method calls
	 * @see type() method for available types
	 */
	public function for( string $type, $sql ) : self
	{
		if( $this->type() === $type )
		{
			foreach( (array) $sql as $entry ) {
				$this->conn->executeStatement( $entry );
			}
		}

		return $this->up();
	}


	/**
	 * Checks if the column exists
	 *
	 * @param string $table Name of the table the column belongs to
	 * @param array|string $name Name of the column or columns
	 * @return TRUE if the columns exists, FALSE if not
	 */
	public function hasColumn( string $table, $name ) : bool
	{
		if( $this->hasTable( $table ) ) {
			return $this->table( $table )->hasColumn( $name );
		}

		return false;
	}


	/**
	 * Checks if the foreign key constraint exists
	 *
	 * @param string $table Name of the table the foreign key constraint belongs to
	 * @param array|string $name Name of the foreign key constraint or constraints
	 * @return TRUE if the foreign key constraint exists, FALSE if not
	 */
	public function hasForeign( string $table, $name ) : bool
	{
		if( $this->hasTable( $table ) ) {
			return $this->table( $table )->hasForeign( $name );
		}

		return false;
	}


	/**
	 * Checks if the index exists
	 *
	 * @param string $table Name of the table the index belongs to
	 * @param array|string $name Name of the index or indexes
	 * @return TRUE if the index exists, FALSE if not
	 */
	public function hasIndex( string $table, $name ) : bool
	{
		if( $this->hasTable( $table ) ) {
			return $this->table( $table )->hasIndex( $name );
		}

		return false;
	}


	/**
	 * Checks if the sequence exists
	 *
	 * @param array|string $name Name of the sequence or sequences
	 * @return TRUE if the sequence exists, FALSE if not
	 */
	public function hasSequence( $name ) : bool
	{
		foreach( (array) $name as $entry )
		{
			if( !$this->to->hasSequence( $entry ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Checks if the table exists
	 *
	 * @param array|string $name Name of the table or tables
	 * @return TRUE if the table exists, FALSE if not
	 */
	public function hasTable( $name ) : bool
	{
		foreach( (array) $name as $entry )
		{
			if( !$this->to->hasTable( $entry ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Inserts a record into the given table
	 *
	 * Warning: The data values are escaped but the table name and column names are not!
	 * Only use fixed strings for table name and column names but no external input!
	 *
	 * @param string $table Name of the table
	 * @param array $data Key/value pairs of column name/value to insert
	 * @return self Same object for fluid method calls
	 */
	public function insert( string $table, array $data ) : self
	{
		$this->conn->insert( $table, $data );
		return $this;
	}


	/**
	 * Returns the ID of the last inserted row into any database table
	 *
	 * @param string|null $seq Name of the sequence generating the ID
	 * @return string Generated ID from the database
	 */
	public function lastId( string $seq = null ) : string
	{
		return $this->conn->lastInsertId( $seq );
	}


	/**
	 * Returns the records from the given table
	 *
	 * Warning: The condition values are escaped but the table name and condition
	 * column names are not! Only use fixed strings for table name and condition
	 * column names but no external input!
	 *
	 * If you need more control over what is returned, use the query builder
	 * from the stmt() method instead.
	 *
	 * @param string $table Name of the table
	 * @param array|null $conditions Key/value pairs of column names and value to compare with
	 * @return array List of associative arrays containing column name/value pairs
	 */
	public function select( string $table, array $conditions = null ) : array
	{
		$idx = 0;
		$list = [];

		$stmt = $this->conn->createQueryBuilder()->select( '*' )->from( $table );

		foreach( $conditions ?? [] as $column => $value ) {
			$stmt->where( $column . ' = ?' )->setParameter( $idx, $value );
		}

		$result = $stmt->executeQuery();

		while( $row = $result->fetchAssociative() )
		{
			foreach( $row as $key => $value )
			{
				if( is_resource( $value ) ) {
					$row[$key] = stream_get_contents( $value );
				}
			}

			$list[] = $row;
		}

		return $list;
	}


	/**
	 * Returns the sequence object for the given name
	 *
	 * If the sequence doesn't exist yet, it will be created.
	 *
	 * @param string $name Name of the sequence
	 * @return \Aimeos\Upscheme\Schema\Sequence Sequence object
	 */
	public function sequence( string $name, \Closure $fcn = null ) : Sequence
	{
		if( $this->to->hasSequence( $name ) ) {
			$seq = $this->to->getSequence( $name );
		} else {
			$seq = $this->to->createSequence( $name );
		}

		$sequence = new Sequence( $this, $seq );

		if( $fcn )
		{
			$fcn( $sequence );
			$this->up();
		}

		return $sequence;
	}


	/**
	 * Returns the query builder for a new SQL statement
	 *
	 * @return \Doctrine\DBAL\Query\QueryBuilder Query builder object
	 */
	public function stmt() : \Doctrine\DBAL\Query\QueryBuilder
	{
		return $this->conn->createQueryBuilder();
	}


	/**
	 * Returns the table object for the given name
	 *
	 * If the table doesn't exist yet, it will be created.
	 *
	 * @param string $name Name of the table
	 * @param \Closure|null $fcn Anonymous function with ($table) parameter creating or updating the table definition
	 * @return \Aimeos\Upscheme\Schema\Table Table object
	 */
	public function table( string $name, \Closure $fcn = null ) : Table
	{
		if( $this->to->hasTable( $name ) ) {
			$dt = $this->to->getTable( $name );
		} else {
			$dt = $this->to->createTable( $name );
		}

		$table = new Table( $this, $dt );

		if( $fcn )
		{
			$fcn( $table );
			$this->up();
		}

		return $table;
	}


	/**
	 * Returns the type of the database
	 *
	 * Possible values are:
	 * - db2
	 * - mysql
	 * - oracle
	 * - postgresql
	 * - mssql
	 * - sqlite
	 *
	 * @return string Database type
	 */
	public function type() : string
	{
		return $this->conn->getDatabasePlatform()->getName();
	}


	/**
	 * Applies the changes to the database schema
	 *
	 * @return self Same object for fluid method calls
	 */
	public function up() : self
	{
		foreach( $this->from->getMigrateToSql( $this->to, $this->conn->getDatabasePlatform() ) as $sql )
		{
			$this->up->info( '  ->  ' . $sql, 'vvv' );
			$this->conn->executeStatement( $sql );
		}

		unset( $this->from );
		$this->from = clone $this->to;

		return $this;
	}


	/**
	 * Updates the records from the given table
	 *
	 * Warning: The condition and data values are escaped but the table name and
	 * column names are not! Only use fixed strings for table name and condition
	 * column names but no external input!
	 *
	 * @param string $table Name of the table
	 * @param array $data Key/value pairs of column name/value to update
	 * @param array|null $conditions Key/value pairs of column names and value to compare with
	 * @return self Same object for fluid method calls
	 */
	public function update( string $table, array $data, array $conditions = null ) : self
	{
		$this->conn->update( $table, $data, $conditions ?? [1 => 1] );
		return $this;
	}
}