<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Upscheme\Schema;


/**
 * Sequence schema class
 */
class Sequence
{
	use \Aimeos\Upscheme\Macro;


	private $db;
	private $sequence;


	/**
	 * Initializes the sequence object
	 *
	 * @param \Aimeos\Upscheme\Schema\DB $db DB schema object
	 * @param \Doctrine\DBAL\Schema\Sequence $sequence Doctrine sequence object
	 */
	public function __construct( \Aimeos\Upscheme\Schema\DB $db, \Doctrine\DBAL\Schema\Sequence $sequence )
	{
		$this->db = $db;
		$this->sequence = $sequence;
	}


	/**
	 * Calls custom methods or passes unknown method calls to the Doctrine table object
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

		return $this->sequence->{$method}( ...$args );
	}


	/**
	 * Returns the value for the given sequence option
	 *
	 * @param string $name Sequence option name
	 * @return mixed Sequence option value
	 */
	public function __get( string $name )
	{
		return $this->{$name}();
	}


	/**
	 * Sets the new value for the given sequence option
	 *
	 * @param string $name Sequence option name
	 * @param mixed Sequence option value
	 */
	public function __set( string $name, $value )
	{
		$this->{$name}( $value );
	}


	/**
	 * Sets the cached size of the sequence or returns the current value
	 *
	 * @param int $value New number of sequence IDs cached by the client or NULL to return current value
	 * @return self|int Same object for setting value, current value without parameter
	 */
	public function cache( int $value = null )
	{
		if( $value === null ) {
			return $this->sequence->getCache();
		}

		$this->sequence->setCache( $value );
		return $this;
	}


	/**
	 * Returns the name of the sequence
	 *
	 * @return string Sequence name
	 */
	public function name()
	{
		return $this->sequence->getName();
	}


	/**
	 * Sets the new start value of the sequence or returns the current value
	 *
	 * @param int $value New start value of the sequence or NULL to return current value
	 * @return self|int Same object for setting value, current value without parameter
	 */
	public function start( int $value = null )
	{
		if( $value === null ) {
			return $this->sequence->getInitialValue();
		}

		$this->sequence->setInitialValue( $value );
		return $this;
	}


	/**
	 * Sets the step size of new sequence values or returns the current value
	 *
	 * @param int $value New step size the sequence is incremented or decremented by or NULL to return current value
	 * @return self|int Same object for setting value, current value without parameter
	 */
	public function step( string $value = null )
	{
		if( $value === null ) {
			return $this->sequence->getAllocationSize();
		}

		$this->sequence->setAllocationSize( $value );
		return $this;
	}


	/**
	 * Applies the changes to the database schema
	 *
	 * @return self Same object for fluid method calls
	 */
	public function up() : self
	{
		$this->db->up();
		return $this;
	}
}