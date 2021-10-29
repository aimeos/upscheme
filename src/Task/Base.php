<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Upscheme\Task;


/**
 * Base class for all setup tasks
 */
abstract class Base implements Iface
{
	use \Aimeos\Upscheme\Macro;


	private static $methods = [];

	private $up;


	/**
	 * Initializes the setup task object
	 *
	 * @param \Aimeos\Upscheme\Up $up Main Upscheme object
	 */
	public function __construct( \Aimeos\Upscheme\Up $up )
	{
		$this->up = $up;
	}


	/**
	 * Passes unknown method calls to the database scheme object
	 *
	 * @param string $method Method name
	 * @param array $args Method arguments
	 * @return mixed Result or database schema object
	 */
	public function __call( string $method, array $args )
	{
		if( $fcn = self::macro( $method ) ) {
			return $this->call( $method, $args );
		}

		return $this->db()->{$method}( ...$args );
	}


	/**
	 * Returns the list of task names which depends on this task
	 *
	 * @return string[] List of task names
	 */
	public function after() : array
	{
		return [];
	}


	/**
	 * Returns the list of task names which this task depends on
	 *
	 * @return string[] List of task names
	 */
	public function before() : array
	{
		return [];
	}


	/**
	 * Returns the database schema for the given connection name
	 *
	 * @param string $name Name of the connection from the configuration or empty string for first one
	 * @param bool $new If a new connection should be created instead of reusing an existing one
	 * @return \Aimeos\Upscheme\Schema\DB DB schema object
	 */
	protected function db( string $name = '', bool $new = false ) : \Aimeos\Upscheme\Schema\DB
	{
		return $this->up->db( $name, $new );
	}


	/**
	 * Outputs the message depending on the verbosity
	 *
	 * @param string $msg Message to display
	 * @param string $verbosity Verbosity level ("v": standard, "vv": more info, "vvv": debug)
	 * @param int $level Level for indenting the message
	 * @return self Same object for fluid method calls
	 */
	protected function info( string $msg, string $verbosity = 'v', int $level = 0 ) : self
	{
		$this->up->info( str_repeat( ' ', $level * 2 ) . $msg, $verbosity );
		return $this;
	}


	/**
	 * Returns the paths for the setup tasks including the given relative paths
	 *
	 * @param string $relpath Relative path to add to the base paths
	 * @return array List of paths which really exist
	 */
	protected function paths( string $relpath = '' ) : array
	{
		$list = [];
		$relpath = DIRECTORY_SEPARATOR . trim( $relpath, DIRECTORY_SEPARATOR );

		foreach( $this->up->paths() as $path )
		{
			$abspath = $path . $relpath;

			if( file_exists( $abspath ) ) {
				$list[] = $abspath;
			}
		}

		return $list;
	}
}