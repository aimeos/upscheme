<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Upscheme\Schema;


/**
 * Database schema manager class
 *
 * @mixin \Doctrine\DBAL\Schema\Schema
 */
class DB
{
	use \Aimeos\Macro\Macroable;


	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	private $conn;

	/**
	 * @var \Doctrine\DBAL\Schema\Schema
	 */
	private $from;

	/**
	 * @var \Doctrine\DBAL\Schema\Schema
	 */
	private $to;

	/**
	 * @var \Aimeos\Upscheme\Up
	 */
	private $up;


	/**
	 * Initializes the database schema manager object
	 *
	 * @param \Aimeos\Upscheme\Up $up Main Upscheme object
	 * @param \Doctrine\DBAL\Connection $conn Doctrine database connection
	 */
	public function __construct( \Aimeos\Upscheme\Up $up, \Doctrine\DBAL\Connection $conn )
	{
		$this->up = $up;
		$this->conn = $conn;

		$this->setup();
	}


	/**
	 * Calls custom methods or passes unknown method calls to the Doctrine schema object
	 *
	 * @param string $method Name of the method
	 * @param array<mixed> $args Method parameters
	 * @return mixed Return value of the called method
	 */
	public function __call( string $method, array $args )
	{
		if( self::macro( $method ) ) {
			return $this->call( $method, ...$args );
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
	public function close() : void
	{
		$this->up();
		$this->conn->close();
	}


	/**
	 * Deletes the records from the given table
	 *
	 * @param string $table Name of the table
	 * @param array<string,mixed> $conditions Key/value pairs of column names and value to compare with
	 * @return self Same object for fluid method calls
	 */
	public function delete( string $table, array $conditions = [] ) : self
	{
		$map = [];
		foreach( $conditions as $column => $value ) {
			$map[$this->qi( $column )] = $value;
		}

		$this->up->info( '  =>  DELETE {"' . $table . '"} WHERE ' . json_encode( $conditions ), 'vvv' );
		$this->conn->delete( $this->qi( $table ), empty( $map ) ? ['1' => 1] : $map );
		return $this;
	}


	/**
	 * Drops the column given by its name if it exists
	 *
	 * @param string $table Name of the table the column belongs to
	 * @param array<string>|string $name Name of the column or columns
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
	 * @param array<string>|string $name Name of the foreign key constraint or constraints
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
	 * @param array<string>|string $name Name of the index or indexes
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
	 * @param array<string>|string $name Name of the sequence or sequences
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
	 * @param array<string>|string $name Name of the table or tables
	 * @return self Same object for fluid method calls
	 */
	public function dropTable( $name ) : self
	{
		$this->up();
		$setup = false;

		// Workaround for Oracle to drop sequence and trigger too
		$manager = $this->getSchemaManager();

		foreach( (array) $name as $entry )
		{
			if( $this->hasTable( $entry ) )
			{
				$manager->dropTable( $this->qi( $entry ) );
				$setup = true;
			}
		}

		return $setup ? $this->setup() : $this;
	}


	/**
	 * Drops the view given by its name if it exists
	 *
	 * @param array<string>|string $name Name of the view or views
	 * @return self Same object for fluid method calls
	 */
	public function dropView( $name ) : self
	{
		$this->up();
		$setup = false;

		$manager = $this->getSchemaManager();

		foreach( (array) $name as $entry )
		{
			if( $this->hasView( $entry ) )
			{
				$manager->dropView( $this->qi( $entry ) );
				$setup = true;
			}
		}

		return $setup ? $this->setup() : $this;
	}


	/**
	 * Executes a custom SQL statement
	 *
	 * The database changes are not applied immediately so always call up()
	 * before executing custom statements to make sure that the tables you want
	 * to use has been created before!
	 *
	 * @param string $sql Custom SQL statement
	 * @param array<int|string,mixed> $params List of positional parameters or associative list of placeholders and parameters
	 * @param array<int|string,mixed> $types List of DBAL data types for the positional or associative placeholder parameters
	 * @return int Number of affected rows
	 */
	public function exec( string $sql, array $params = [], array $types = [] ) : int
	{
		return $this->conn->executeStatement( $sql, $params, $types );
	}


	/**
	 * Executes a custom SQL statement if the database is of the given type
	 *
	 * The database changes are not applied immediately so always call up()
	 * before executing custom statements to make sure that the tables you want
	 * to use has been created before!
	 *
	 * @param array<string>|string $for Database type the statement should be executed for ("mysql", "postgresql", "sqlite", "mssql", "oracle", "db2")
	 * @param array<string>|string $sql Custom SQL statement or statements
	 * @return self Same object for fluid method calls
	 */
	public function for( $for, $sql ) : self
	{
		if( in_array( $this->type(), (array) $for ) )
		{
			foreach( (array) $sql as $entry ) {
				$this->conn->executeStatement( $entry );
			}
		}

		return $this;
	}


	/**
	 * Checks if the columns exists
	 *
	 * @param string $table Name of the table the column belongs to
	 * @param array<string>|string $name Name of the column or columns
	 * @return bool TRUE if the columns exists, FALSE if not
	 */
	public function hasColumn( string $table, $name ) : bool
	{
		if( $this->hasTable( $table ) ) {
			return $this->table( $table )->hasColumn( $name );
		}

		return false;
	}


	/**
	 * Checks if the foreign key constraints exists
	 *
	 * @param string $table Name of the table the foreign key constraint belongs to
	 * @param array<string>|string $name Name of the foreign key constraint or constraints
	 * @return bool TRUE if the foreign key constraint exists, FALSE if not
	 */
	public function hasForeign( string $table, $name ) : bool
	{
		if( $this->hasTable( $table ) ) {
			return $this->table( $table )->hasForeign( $name );
		}

		return false;
	}


	/**
	 * Checks if the indexes exists
	 *
	 * @param string $table Name of the table the index belongs to
	 * @param array<string>|string $name Name of the index or indexes
	 * @return bool TRUE if the index exists, FALSE if not
	 */
	public function hasIndex( string $table, $name ) : bool
	{
		if( $this->hasTable( $table ) ) {
			return $this->table( $table )->hasIndex( $name );
		}

		return false;
	}


	/**
	 * Checks if the sequences exists
	 *
	 * @param array<string>|string $name Name of the sequence or sequences
	 * @return bool TRUE if the sequence exists, FALSE if not
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
	 * Checks if the tables exists
	 *
	 * @param array<string>|string $name Name of the table or tables
	 * @return bool TRUE if the table exists, FALSE if not
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
	 * Checks if the view exists
	 *
	 * @param array<string>|string $name Name of the view or views
	 * @return bool TRUE if the view exists, FALSE if not
	 */
	public function hasView( $name ) : bool
	{
		$views = [];
		$manager = $this->getSchemaManager();

		foreach( $manager->listViews() as $view ) {
			$views[$view->getName()] = $view;
		}

		$list = $manager->getSchemaSearchPaths();
		$list[] = '';

		foreach( (array) $name as $entry )
		{
			foreach( $list as $schema )
			{
				$key = $schema ? $schema . '.' . $entry : $entry;

				if( isset( $views[$key] ) ) {
					continue 2;
				}
			}

			return false;
		}

		return true;
	}


	/**
	 * Inserts a record into the given table
	 *
	 * @param string $table Name of the table
	 * @param array<string,mixed> $data Key/value pairs of column name/value to insert
	 * @return self Same object for fluid method calls
	 */
	public function insert( string $table, array $data ) : self
	{
		$map = [];
		foreach( $data as $column => $value ) {
			$map[$this->qi( $column )] = $value;
		}

		$this->up->info( '  =>  INSERT {"' . $table . '"} VALUES ' . json_encode( $data ), 'vvv' );
		$this->conn->insert( $this->qi( $table ), $map );
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
		if( $seq && $this->type() === 'oracle' )
		{
			$sql = sprintf( 'SELECT %1$s.CURRVAL FROM DUAL', $this->qi( $seq ) );

			if( ( $result = $this->query( $sql )->fetchOne() ) === false ) {
				throw new \RuntimeException( sprintf( 'Sequence "%1$s" does not exist', $seq ) );
			}

			return $result;
		}

		return $this->conn->lastInsertId( $seq ? $this->qi( $seq ) : null );
	}


	/**
	 * Returns the name of the database
	 *
	 * @return string Database name
	 */
	public function name() : string
	{
		return $this->to->getName();
	}


	/**
	 * Quotes a value
	 *
	 * @param mixed $value Value to use in a non-prepared SQL query
	 * @param mixed $type DBAL parameter type
	 * @return string Quoted value
	 */
	public function q( $value, $type = \Doctrine\DBAL\ParameterType::STRING ) : string
	{
		return $this->conn->quote( $value, $type );
	}


	/**
	 * Quotes a database identifier
	 *
	 * @param string $identifier Identifier like table or column name
	 * @return string Quoted identifier
	 */
	public function qi( string $identifier ) : string
	{
		return $this->conn->quoteIdentifier( $identifier );
	}


	/**
	 * Executes a custom SQL query
	 *
	 * @param string $sql Custom SQL statement
	 * @param array<int|string,mixed> $params List of positional parameters or associative list of placeholders and parameters
	 * @param array<int|string,mixed> $types List of DBAL data types for the positional or associative placeholder parameters
	 * @return \Doctrine\DBAL\Result DBAL result set object
	 */
	public function query( string $sql, array $params = [], array $types = [] ) : \Doctrine\DBAL\Result
	{
		return $this->conn->executeQuery( $sql, $params, $types );
	}


	/**
	 * Renames a column or a list of column which belong to the given table
	 *
	 * @param string $table Name of the table
	 * @param array<string,string>|string $from Column name or array of old/new column names
	 * @param string|null $to New column name or NULL if first parameter is an array
	 * @return self Same object for fluid method calls
	 */
	public function renameColumn( string $table, $from, string $to = null ) : self
	{
		$this->up();
		$setup = false;

		if( !is_array( $from ) ) {
			$from = [$from => $to];
		}

		foreach( $from as $name => $to )
		{
			if( $this->hasColumn( $table, $name ) )
			{
				if( !$to )
				{
					$msg = sprintf( 'Renaming "%1$s.%2$s" column requires a non-empty new name', $table, $name );
					throw new \RuntimeException( $msg );
				}

				$sql = $this->getColumnSQL( $table, $name, $to );
				$this->up->info( '  ->  ' . $sql, 'vvv' );

				$this->conn->executeStatement( $sql );
				$setup = true;
			}
		}

		return $setup ? $this->setup() : $this;
	}


	/**
	 * Renames an index or a list of indexes which belong to the given table
	 *
	 * @param array<string,string>|string $from Index name or array of old/new index names (if new index name is NULL, it will be generated)
	 * @param string|null $to New index name or NULL for autogenerated name (ignored if first parameter is an array)
	 * @return self Same object for fluid method calls
	 */
	public function renameIndex( string $table, $from, string $to = null ) : self
	{
		$this->table( $table )->renameIndex( $from, $to );
		return $this;
	}


	/**
	 * Renames a table or a list of tables which belong to the current schema
	 *
	 * @param array<string,string>|string $from Table name or array of old/new table names
	 * @param string|null $to New table name or ignored if first parameter is an array
	 * @return self Same object for fluid method calls
	 */
	public function renameTable( $from, string $to = null ) : self
	{
		$this->up();
		$setup = false;

		if( !is_array( $from ) ) {
			$from = [$from => $to];
		}

		$manager = $this->getSchemaManager();

		foreach( $from as $name => $to )
		{
			if( $this->hasTable( $name ) )
			{
				if( !$to )
				{
					$msg = sprintf( 'Renaming table "%1$s" requires a non-empty new name', $name );
					throw new \RuntimeException( $msg );
				}

				$manager->renameTable( $this->qi( $name ), $this->qi( $to ) );
				$setup = true;
			}
		}

		return $setup ? $this->setup() : $this;
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
	 * @param array<string>|null $conditions Key/value pairs of column names and value to compare with
	 * @return array<int,array<string,mixed>> List of associative arrays containing column name/value pairs
	 */
	public function select( string $table, array $conditions = null ) : array
	{
		$idx = 0;
		$list = [];

		$builder = $this->conn->createQueryBuilder()->select( '*' )->from( $this->qi( $table ) );

		foreach( $conditions ?? [] as $column => $value ) {
			$builder->andWhere( $this->qi( $column ) . ' = ?' )->setParameter( $idx++, $value );
		}

		$result = method_exists( $builder, 'executeQuery' ) ? $builder->executeQuery() : $builder->execute();

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
	 * If the sequence doesn't exist yet, it will be created. To persist the changes
	 * in the database, you have to call up() at the end.
	 *
	 * @param string $name Name of the sequence
	 * @param \Closure|null $fcn Anonymous function with ($sequence) parameter creating or updating the sequence definition
	 * @return \Aimeos\Upscheme\Schema\Sequence Sequence object
	 */
	public function sequence( string $name, \Closure $fcn = null ) : Sequence
	{
		if( $this->to->hasSequence( $name ) ) {
			$seq = $this->to->getSequence( $name );
		} else {
			$seq = $this->to->createSequence( $this->qi( $name ) );
		}

		$sequence = new Sequence( $this, $seq );

		if( $fcn ) {
			$fcn( $sequence );
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
	 * If the table doesn't exist yet, it will be created. To persist the changes
	 * in the database, you have to call up() at the end.
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
			$dt = $this->to->createTable( $this->qi( $name ) );
		}

		$table = new Table( $this, $dt );

		if( $fcn ) {
			$fcn( $table );
		}

		return $table;
	}


	/**
	 * Returns the type of the database
	 *
	 * Possible values are:
	 * - db2
	 * - mssql
	 * - mysql
	 * - oracle
	 * - postgresql
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
	 * @param string $table Name of the table
	 * @param array<string,mixed> $data Key/value pairs of column name/value to update
	 * @param array<string,mixed> $conditions Key/value pairs of column names and value to compare with
	 * @return self Same object for fluid method calls
	 */
	public function update( string $table, array $data, array $conditions = [] ) : self
	{
		$map = $values = [];

		foreach( $data as $column => $value ) {
			$map[$this->qi( $column )] = $value;
		}

		foreach( $conditions as $column => $value ) {
			$values[$this->qi( $column )] = $value;
		}

		$this->up->info( '  =>  UPDATE {"' . $table . '"} SET ' . json_encode( $data ) . ' WHERE ' . json_encode( $conditions ), 'vvv' );
		$this->conn->update( $this->qi( $table ), $map, empty( $values ) ? ['1' => 1] : $values );
		return $this;
	}


	/**
	 * Creates a view with the given name if it doesn't exist yet
	 *
	 * If the view doesn't exist yet, it will be created. Otherwise, nothing
	 * will happen.
	 *
	 * @param string $name Name of the view
	 * @param string $sql SQL statement to create the view
	 * @param array<string>|string|null $for Database type this SQL should be used for ("mysql", "postgresql", "sqlite", "mssql", "oracle", "db2")
	 * @return self Same object for fluid method calls
	 */
	public function view( string $name, string $sql, $for = null ) : self
	{
		$manager = $this->getSchemaManager();

		if( !$this->hasView( $name ) && ( $for === null || in_array( $this->type(), (array) $for ) ) ) {
			$manager->createView( new \Doctrine\DBAL\Schema\View( $this->qi( $name ), $sql ) );
		}

		return $this;
	}


	/**
	 * Returns the column declaration as SQL string
	 *
	 * @param string $table Table name
	 * @param string $name Old column name
	 * @param string $to New column name
	 * @return string SQL column declaration
	 */
	protected function getColumnSQL( string $table, string $name, string $to ) : string
	{
		$qtable = $this->qi( $table );
		$qname = $this->qi( $name );
		$qto = $this->qi( $to );

		switch( $this->type() )
		{
			case 'mssql':
				$sql = sprintf( 'sp_rename \'%1$s.%2$s\', \'%3$s\', \'COLUMN\'', $qtable, $qname, $qto );
				break;
			case 'mysql':
				$col = $this->to->getTable( $table )->getColumn( $name );
				$sql = $this->conn->getDatabasePlatform()->getColumnDeclarationSQL( $to, $col->toArray() );
				$sql = sprintf( 'ALTER TABLE %1$s CHANGE %2$s %3$s', $qtable, $qname, $sql );
				break;
			default:
				$sql = sprintf( 'ALTER TABLE %1$s RENAME COLUMN %2$s TO %3$s', $qtable, $qname, $qto );
		}

		return $sql;
	}


	/**
	 * Returns the Doctrine schema manager
	 *
	 * @return \Doctrine\DBAL\Schema\AbstractSchemaManager Doctrine schema manager
	 */
	protected function getSchemaManager() : \Doctrine\DBAL\Schema\AbstractSchemaManager
	{
		return method_exists( $this->conn, 'createSchemaManager' )
			? $this->conn->createSchemaManager()
			: $this->conn->getSchemaManager();
	}


	/**
	 * Loads the actual Doctrine schema for the current database
	 *
	 * @return self Same object for fluid method calls
	 */
	protected function setup() : self
	{
		$this->to = $this->getSchemaManager()->createSchema();
		$this->from = clone $this->to;

		return $this;
	}
}
