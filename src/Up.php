<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Upscheme;


/**
 * Main class for updating database schemas
 */
class Up
{
	use \Aimeos\Macro\Macroable;


	/**
	 * @var array<string,string|array<string,string>>
	 */
	private $config;

	/**
	 * @var array<string,\Aimeos\Upscheme\Task\Iface>
	 */
	private $tasks;

	/**
	 * @var array<string>
	 */
	private $tasksDone;

	/**
	 * @var array<string,array<string>>
	 */
	private $dependencies;

	/**
	 * @var int
	 */
	private $verbose = 0;

	/**
	 * @var array<string>
	 */
	private $paths = [];

	/**
	 * @var array<string,\Aimeos\Upscheme\Schema\DB>
	 */
	private $db = [];


	/**
	 * Initializes the new object
	 *
	 * @param array<string,string|array<string,string>> $config One or more database configuration parameters
	 * @param array<string>|string $paths One or more paths to the tasks which updates the database
	 */
	public function __construct( array $config, $paths )
	{
		if( empty( $config ) ) {
			throw new \RuntimeException( 'No database configuration passed' );
		}

		if( empty( $paths ) ) {
			throw new \RuntimeException( 'No path for the tasks passed' );
		}

		if( spl_autoload_register( static::macro( 'autoload' ) ?: [$this, 'autoload'] ) === false ) {
			throw new \RuntimeException( 'Unable to register autoloader' );
		}

		$this->config = $config;
		$this->paths = (array) $paths;

		$this->macros();
	}


	/**
	 * Creates a new Upscheme object initialized with the given configuration and paths
	 *
	 * @param array<string,string|array<string,string>> $config One or more database configuration parameters
	 * @param array<string>|string $paths One or more paths to the tasks which updates the database
	 * @return \Aimeos\Upscheme\Up Upscheme object
	 */
	public static function use( array $config, $paths ) : self
	{
		return new self( $config, $paths );
	}


	/**
	 * Returns the DB schema for the passed connection name
	 *
	 * @param string $name Name of the connection from the configuration or empty string for first one
	 * @param bool $new If a new connection should be created instead of reusing an existing one
	 * @return \Aimeos\Upscheme\Schema\DB DB schema object
	 */
	public function db( string $name = '', bool $new = false ) : \Aimeos\Upscheme\Schema\DB
	{
		if( !isset( $this->config[$name] ) ) {
			$cfg = is_array( $first = reset( $this->config ) ) ? $first : $this->config; $name = '';
		} else {
			$cfg = $this->config[$name];
		}

		if( !isset( $this->db[$name] ) ) {
			$this->db[$name] = new \Aimeos\Upscheme\Schema\DB( $this, $this->connect( $cfg ) );
		}

		return $new ? clone $this->db[$name] : $this->db[$name];
	}


	/**
	 * Outputs the message depending on the passed verbosity level
	 *
	 * @param string $msg Message to display
	 * @param mixed $level Verbosity level (empty: always, v: notice: vv: info, vvv: debug)
	 * @return self Same object for fluid method calls
	 */
	public function info( string $msg, $level = 'v' ) : self
	{
		if( $fcn = static::macro( 'info' ) ) {
			$fcn( $msg, $level );
		} elseif( strlen( (string) $level ) <= $this->verbose ) {
			echo $msg . PHP_EOL;
		}

		return $this;
	}


	/**
	 * Returns the paths for the setup tasks
	 *
	 * @return array<string> List of paths
	 */
	public function paths() : array
	{
		return $this->paths;
	}


	/**
	 * Executes the tasks to update the database
	 *
	 * @return self Same object for fluid method calls
	 */
	public function up() : self
	{
		$this->tasksDone = [];
		$this->dependencies = [];
		$this->tasks = $this->createTasks( $this->paths() );

		foreach( $this->tasks as $name => $task )
		{
			foreach( (array) $task->before() as $taskname ) {
				$this->dependencies[$taskname][] = $name;
			}

			foreach( (array) $task->after() as $taskname ) {
				$this->dependencies[$name][] = $taskname;
			}
		}

		foreach( $this->tasks as $taskname => $task ) {
			$this->runTasks( [$taskname] );
		}

		return $this;
	}


	/**
	 * Sets the verbosity level
	 *
	 * @param mixed $level Verbosity level (empty: none, v: notice: vv: info, vvv: debug)
	 * @return self Same object for fluid method calls
	 */
	public function verbose( $level = 'v' ) : self
	{
		$this->verbose = ( $fcn = static::macro( 'verbose' ) ) ? $fcn( $level ) : strlen( (string) $level );
		return $this;
	}


	/**
	 * Autoloader for setup tasks.
	 *
	 * @param string $classname Name of the class to load
	 * @return bool True if class was found, false if not
	 */
	protected function autoload( string $classname ) : bool
	{
		if( !strncmp( $classname, 'Aimeos\Upscheme\Task\\', 21 ) )
		{
			$fileName = substr( $classname, 21 ) . '.php';

			foreach( $this->paths() as $path )
			{
				$file = $path . '/' . $fileName;

				if( file_exists( $file ) === true && ( include_once $file ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Creates a new database connection from the given configuration
	 *
	 * @param array<string,string> $cfg Database configuration
	 * @return \Doctrine\DBAL\Connection New DBAL database connection
	 */
	protected function connect( array $cfg ) : \Doctrine\DBAL\Connection
	{
		$cfg['driverOptions'][\PDO::ATTR_CASE] = \PDO::CASE_NATURAL;
		$cfg['driverOptions'][\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
		$cfg['driverOptions'][\PDO::ATTR_ORACLE_NULLS] = \PDO::NULL_NATURAL;
		$cfg['driverOptions'][\PDO::ATTR_STRINGIFY_FETCHES] = false;

		if( $fcn = static::macro( 'connect' ) ) {
			return $fcn( $cfg );
		}

		$conn = \Doctrine\DBAL\DriverManager::getConnection( $cfg );

		if( in_array( $cfg['driver'], ['oci8', 'pdo_oci'] ) ) {
			$conn->executeStatement( "ALTER SESSION SET NLS_TIME_FORMAT='HH24:MI:SS' NLS_DATE_FORMAT='YYYY-MM-DD' NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS' NLS_TIMESTAMP_TZ_FORMAT='YYYY-MM-DD HH24:MI:SS TZH:TZM' NLS_NUMERIC_CHARACTERS='.,'" );
		}

		return $conn;
	}


	/**
	 * Creates the tasks from the given directories
	 *
	 * @param array<string> $paths List of paths containing task classes
	 * @return array<\Aimeos\Upscheme\Task\Iface> List of task objects
	 */
	protected function createTasks( array $paths ) : array
	{
		$tasks = [];

		foreach( $paths as $path )
		{
			foreach( new \DirectoryIterator( $path ) as $item )
			{
				if( $item->isDir() === true || substr( $item->getFilename(), -4 ) != '.php' ) { continue; }

				include_once $item->getPathName();

				$taskname = substr( $item->getFilename(), 0, -4 );
				$classname = '\Aimeos\Upscheme\Task\\' . $taskname;

				if( class_exists( $classname ) === false ) {
					throw new \RuntimeException( sprintf( 'Class "%1$s" not found', $classname ) );
				}

				$interface = \Aimeos\Upscheme\Task\Iface::class;
				$task = ( $fcn = static::macro( 'createTask' ) ) ? $fcn( $classname ) : new $classname( $this );

				if( ( $task instanceof $interface ) === false ) {
					throw new \RuntimeException( sprintf( 'Class "%1$s" doesn\'t implement "%2$s"', $classname, $interface ) );
				}

				$task->_filename = $item->getPathName();
				$tasks[$taskname] = $task;
			}
		}

		ksort( $tasks );
		return $tasks;
	}


	/**
	 * Adds default macros which can be overwritten
	 */
	protected function macros()
	{
		\Aimeos\Upscheme\Schema\Table::macro( 'id', function( string $name = null ) : Schema\Column {
			return $this->integer( $name ?: 'id' )->seq( true )->primary();
		} );

		\Aimeos\Upscheme\Schema\Table::macro( 'bigid', function( string $name = null ) : Schema\Column {
			return $this->bigint( $name ?: 'id' )->seq( true )->primary();
		} );
	}


	/**
	 * Executes each task depending of the task dependencies
	 *
	 * @param array<string> $tasknames List of task names
	 * @param array<string> $stack List of task names that are scheduled after this task
	 */
	protected function runTasks( array $tasknames, array $stack = [] ) : void
	{
		$dir = getcwd();
		$dirlen = strlen( $dir );

		foreach( $tasknames as $taskname )
		{
			if( in_array( $taskname, $this->tasksDone ) ) {
				continue;
			}

			if( in_array( $taskname, $stack ) )
			{
				$msg = 'Circular dependency for "%1$s" detected. Task stack: %2$s';
				throw new \RuntimeException( sprintf( $msg, $taskname, join( ', ', $stack ) ) );
			}

			$stack[] = $taskname;

			if( isset( $this->dependencies[$taskname] ) ) {
				$this->runTasks( (array) $this->dependencies[$taskname], $stack );
			}

			if( isset( $this->tasks[$taskname] ) )
			{
				$start = microtime( true );
				$file = $this->tasks[$taskname]->_filename;

				if( !strncmp( $file, $dir, $dirlen ) ) {
					$file = ltrim( substr( $file, $dirlen ), '/' );
				}

				$this->info( 'Migrating: ' . $file, 'v' );
				$this->tasks[$taskname]->up();

				foreach( $this->db as $db ) {
					$db->up();
				}

				$this->info( 'Migrated:  ' . $file . ' (' . round( ( microtime( true ) - $start ) * 1000, 2 ) . 'ms)', 'v' );
			}
			else
			{
				$this->info( 'Missing:   ' . $taskname, 'v' );
			}

			$this->tasksDone[] = $taskname;
		}
	}
}
