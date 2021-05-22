<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Upscheme;


/**
 * Macro trait for registering custom methods
 */
trait Macro
{
	private static $macros = [];


	/**
	 * Registers a custom method
	 *
	 * @param string $method Name of the method
	 * @param \Closure|null $fcn Anonymous function which receives the same parameters as the original method
	 * @return \Closure|null Registered anonymous function or NULL if none has been registered
	 */
	public static function macro( string $method, \Closure $fcn = null ) : ?\Closure
	{
		if( $fcn ) {
			self::$macros[$method] = $fcn;
		}

		return self::$macros[$method] ?? null;
	}


	/**
	 * Resets the custom methods
	 *
	 * @param string $method Method name or NULL for all custom methods
	 */
	public static function reset( string $method = null )
	{
		if( $method ) {
			unset( self::$macros[$method] );
		} else {
			self::$macros = [];
		}
	}


	/**
	 * Passes unknown method calls to the custom methods
	 *
	 * @param string $method Method name
	 * @param array $args Method arguments
	 * @return mixed Result or method call
	 */
	public function __call( string $method, array $args )
	{
		return $this->call( $method, $args );
	}


	/**
	 * Passes unknown method calls to the custom methods
	 *
	 * @param string $method Method name
	 * @param array $args Method arguments
	 * @return mixed Result or method call
	 */
	public function call( string $method, array $args )
	{
		if( $fcn = self::macro( $method ) ) {
			return call_user_func_array( $fcn->bindTo( $this, static::class ), $args );
		}

		throw new \BadMethodCallException( sprintf( 'Unknown method "%1$s" in %2$s', $method, __CLASS__ ) );
	}
}
