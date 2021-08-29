<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Upscheme\Schema;


/**
 * Column schema class
 */
class Column
{
	use \Aimeos\Upscheme\Macro;


	private $db;
	private $table;
	private $column;


	/**
	 * Initializes the table object
	 *
	 * @param \Aimeos\Upscheme\Schema\DB $db DB schema object
	 * @param \Aimeos\Upscheme\Schema\Table $table Table schema object
	 * @param \Doctrine\DBAL\Schema\Column $column Doctrine column object
	 */
	public function __construct( DB $db, Table $table, \Doctrine\DBAL\Schema\Column $column )
	{
		$this->db = $db;
		$this->table = $table;
		$this->column = $column;
	}


	/**
	 * Calls custom methods or passes unknown method calls to the Doctrine column object
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

		return $this->column->{$method}( ...$args );
	}


	/**
	 * Returns the value for the given column option
	 *
	 * @param string $name Column option name
	 * @return mixed Column option value
	 */
	public function __get( string $name )
	{
		return $this->opt( $name );
	}


	/**
	 * Sets the new value for the given column option
	 *
	 * @param string $name Column option name
	 * @param mixed Column option value
	 */
	public function __set( string $name, $value )
	{
		$this->opt( $name, $value );
	}


	/**
	 * Sets the column option value or returns the current value
	 *
	 * @param string $option Column option name
	 * @param mixed $value New column option value or NULL to return current value
	 * @param array|string|null $for Database type this option should be used for ("mysql", "postgresql", "sqlite", "mssql", "oracle", "db2")
	 * @return self|mixed Same object for setting the value, current value without parameter
	 */
	public function opt( string $option, $value = null, $for = null )
	{
		if( $value === null ) {
			return $this->column->getCustomSchemaOption( $option );
		}

		if( $for === null || in_array( $this->db->type(), (array) $for ) ) {
			$this->column->setCustomSchemaOption( $option, $value );
		}

		return $this;
	}


	/**
	 * Sets the column as autoincrement or returns the current value
	 *
	 * This method is an alias for seq().
	 *
	 * @param bool|null $value New autoincrement flag or NULL to return current value
	 * @return self|bool Same object for setting the value, current value without parameter
	 */
	public function autoincrement( bool $value = null )
	{
		return $this->seq( $value );
	}


	/**
	 * Sets the column charset or returns the current value
	 *
	 * @param mixed $value New column charset or NULL to return current value
	 * @return self|mixed Same object for setting the value, current value without parameter
	 */
	public function charset( $value = null )
	{
		return $this->opt( 'charset', $value );
	}


	/**
	 * Sets the column collation or returns the current value
	 *
	 * @param mixed $value New column collation or NULL to return current value
	 * @return self|mixed Same object for setting the value, current value without parameter
	 */
	public function collation( $value = null )
	{
		return $this->opt( 'collation', $value );
	}


	/**
	 * Sets the column comment or returns the current value
	 *
	 * @param string|null $value New column comment or NULL to return current value
	 * @return self|string Same object for setting the value, current value without parameter
	 */
	public function comment( string $value = null )
	{
		if( $value === null ) {
			return $this->column->getComment();
		}

		$this->column->setComment( $value );
		return $this;
	}


	/**
	 * Sets the column default value or returns the current value
	 *
	 * @param mixed $value New column default value or NULL to return current value
	 * @return self|mixed Same object for setting the value, current value without parameter
	 */
	public function default( $value = null )
	{
		if( $value === null ) {
			return $this->column->getDefault();
		}

		$this->column->setDefault( $value );
		return $this;
	}


	/**
	 * Sets the column fixed flag or returns the current value
	 *
	 * @param string|null $value New column fixed flag or NULL to return current value
	 * @return self|string Same object for setting the value, current value without parameter
	 */
	public function fixed( bool $value = null )
	{
		if( $value === null ) {
			return $this->column->getFixed();
		}

		$this->column->setFixed( $value );
		return $this;
	}


	/**
	 * Sets the column length or returns the current value
	 *
	 * @param string|null $value New column length or NULL to return current value
	 * @return self|string Same object for setting the value, current value without parameter
	 */
	public function length( int $value = null )
	{
		if( $value === null ) {
			return $this->column->getLength();
		}

		$this->column->setLength( $value );
		return $this;
	}


	/**
	 * Returns the name of the column
	 *
	 * @return string Column name
	 */
	public function name() : string
	{
		return $this->column->getName();
	}


	/**
	 * Sets the column null flag or returns the current value
	 *
	 * @param string|null $value New column null flag or NULL to return current value
	 * @return self|string Same object for setting the value, current value without parameter
	 */
	public function null( bool $value = null )
	{
		if( $value === null ) {
			return !$this->column->getNotnull();
		}

		$this->column->setNotnull( !$value );
		return $this;
	}


	/**
	 * Sets the column precision or returns the current value
	 *
	 * @param string|null $value New column precision value or NULL to return current value
	 * @return self|string Same object for setting the value, current value without parameter
	 */
	public function precision( int $value = null )
	{
		if( $value === null ) {
			return $this->column->getPrecision();
		}

		$this->column->setPrecision( $value );
		return $this;
	}


	/**
	 * Sets the column scale or returns the current value
	 *
	 * @param string|null $value New column scale value or NULL to return current value
	 * @return self|string Same object for setting the value, current value without parameter
	 */
	public function scale( int $value = null )
	{
		if( $value === null ) {
			return $this->column->getScale();
		}

		$this->column->setScale( $value );
		return $this;
	}


	/**
	 * Sets the column as autoincrement or returns the current value
	 *
	 * @param bool|null $value New autoincrement flag or NULL to return current value
	 * @return self|bool Same object for setting the value, current value without parameter
	 */
	public function seq( bool $value = null )
	{
		if( $value === null ) {
			return $this->column->getAutoincrement();
		}

		$this->column->setAutoincrement( $value );
		return $this;
	}


	/**
	 * Sets the column type or returns the current value
	 *
	 * @param string|null $value New column type or NULL to return current value
	 * @return self|string Same object for setting the value, current value without parameter
	 */
	public function type( string $value = null )
	{
		if( $value === null ) {
			return $this->column->getType()->getName();
		}

		$this->column->setType( \Doctrine\DBAL\Types\Type::getType( $value ) );
		return $this;
	}


	/**
	 * Sets the column unsigned flag or returns the current value
	 *
	 * @param bool|null $value New column unsigned flag or NULL to return current value
	 * @return self|bool Same object for setting the value, current value without parameter
	 */
	public function unsigned( bool $value = null )
	{
		if( $value === null ) {
			return $this->column->getUnsigned();
		}

		$this->column->setUnsigned( $value );
		return $this;
	}


	/**
	 * Creates a regular index for the column
	 *
	 * @param string|null $name Name of the index or NULL to generate automatically
	 * @return self Same object for fluid method calls
	 */
	public function index( string $name = null ) : self
	{
		$this->table->index( [$this->name()], $name );
		return $this;
	}


	/**
	 * Creates a primary index for the column
	 *
	 * @param string|null $name Name of the index or NULL to generate automatically
	 * @return self Same object for fluid method calls
	 */
	public function primary( string $name = null ) : self
	{
		$this->table->primary( [$this->name()], $name );
		return $this;
	}


	/**
	 * Creates a spatial index for the column
	 *
	 * @param string|null $name Name of the index or NULL to generate automatically
	 * @return self Same object for fluid method calls
	 */
	public function spatial( string $name = null ) : self
	{
		$this->table->spatial( [$this->name()], $name );
		return $this;
	}


	/**
	 * Creates an unique index for the column
	 *
	 * @param string|null $name Name of the index or NULL to generate automatically
	 * @return self Same object for fluid method calls
	 */
	public function unique( string $name = null ) : self
	{
		$this->table->unique( $this->name(), $name );
		return $this;
	}


	/**
	 * Applies the changes to the database schema
	 *
	 * @return self Same object for fluid method calls
	 */
	public function up() : self
	{
		$this->table->up();
		return $this;
	}
}