<a class="badge" href="https://github.com/aimeos/upscheme/actions"><img src="https://circleci.com/gh/aimeos/aimeos-core.svg?style=shield" alt="Build Status" height="20"></a>
<a class="badge" href="https://coveralls.io/github/aimeos/upscheme"><img src="https://coveralls.io/repos/github/aimeos/upscheme/badge.svg" alt="Coverage Status" height="20"></a>
<a class="badge" href="https://packagist.org/packages/aimeos/upscheme"><img src="https://poser.pugx.org/aimeos/upscheme/license.svg" alt="License" height="20"></a>

# Upscheme: Database schema updates made easy

Easy to use PHP package for updating the database schema of your application
and migrate data between versions.

```bash
composer req aimeos/upscheme
```

**Table of contents**

* [Why Upscheme](#why-upscheme)
* [Integrating Upscheme](#integrate-upscheme)
* [Writing migrations](#writing-migrations)
  * [Schemas](#schemas)
  * [Messages](#messages)
  * [Dependencies](#dependencies)
* [Schema objects](#schema-objects)
  * [Database](#database)


## Why Upscheme

Migrations are like version control for your database. They allow you to record all changes and share them with others so they get the exact same state in their installation.

For upgrading relational database schemas, two packages are currently used most often: Doctrine DBAL and Doctrine migrations. While Doctrine DBAL does a good job in abstracting the differences of several database implementations, it's API requires writing a lot of code. Doctrine migrations on the other site has some drawbacks which make it hard to use in all applications that support 3rd party extensions.

### Doctrine DBAL drawbacks

The API of DBAL is very verbose and you need to write lots of code even for simple things. Upscheme uses Doctrine DBAL to offer an easy to use API for upgrading the database schema of your application with minimal code. Let's compare some example code you have to write for DBAL and for Upscheme in a migration.

#### DBAL

```php
$dbalManager = $conn->createSchemaManager();
$from = $manager->createSchema();
$to = $manager->createSchema();

if( $to->hasTable( 'test' ) ) {
	$table = $to->getTable( 'test' );
} else {
	$table = $to->createTable( 'test' );
}

$table->addOption( 'engine', 'InnoDB' );

$table->addColumn( 'id', 'integer', ['autoincrement' => true] );
$table->addColumn( 'domain', 'string', ['length' => 32] );

if( $conn->getDatabasePlatform()->getName() === 'mysql' ) {
	$table->addColumn( 'code', 'string', ['length' => 64, 'customSchemaOptions' => ['charset' => 'binary']] );
} else {
	$table->addColumn( 'code', 'string', ['length' => 64]] );
}

$table->addColumn( 'label', 'string', ['length' => 255] );
$table->addColumn( 'pos', 'integer', ['default' => 0] );
$table->addColumn( 'status', 'smallint', [] );
$table->addColumn( 'mtime', 'datetime', [] );
$table->addColumn( 'ctime', 'datetime', [] );
$table->addColumn( 'editor', 'string', ['length' => 255] );

$table->setPrimaryKey( ['id'] );
$table->addUniqueIndex( ['domain', 'code'] );
$table->addIndex( ['status', 'pos'] );

foreach( $from->getMigrateToSql( $to, $conn->getDatabasePlatform() ) as $sql ) {
	$conn->executeStatement( $sql );
}
```

#### Upscheme

```php
$this->db()->table( 'test', function( $t ) {
	$t->engine = 'InnoDB';

	$t->id();
	$t->string( 'domain', 32 );
	$t->string( 'code', 64 )->opt( 'charset', 'binary', 'mysql' );
	$t->string( 'label', 255 );
	$t->int( 'pos' )->default( 0 );
	$t->smallint( 'status' );
	$t->default();

	$t->unique( ['domain', 'code'] );
	$t->index( ['status', 'pos'] );
} );
```

### Doctrine Migration drawbacks

Doctrine Migration relies on migration classes that are named by the time they have been created to ensure a certain order. Furthermore, it stores which migrations has been executed in a table of your database. There are two major problems that arise from that.

If your application supports 3rd party extensions, these extensions are likely to add columns to existing tables and migrate data themselves. As there's no way to define dependencies between migrations, it can get almost impossible to run migrations in an application with several 3rd party extensions without conflicts. To avoid that, Upscheme offers easy to use `before()` and `after()` methods in each migration task where the tasks can define its dependencies to other tasks.

Because Doctrine Migrations uses a database table to record which migration already has been executed, these records can get easily out of sync in case of problems. Contrary, Upscheme only relies on the actual schema so it's possible to upgrade from any state, regardless of what has happend before.

Doctrine Migrations also supports the reverse operations in `down()` methods so you can roll back migrations which Upscheme does not. Experience has shown that it's often impossible to roll back migrations, e.g. after adding a new colum, migrating the data of an existing column and dropping the old column afterwards. If the migration of the data was lossy, you can't recreate the same state in a `down()` method. The same is the case if you've dropped a table. Thus, Upscheme only offers scheme upgrading but no downgrading to avoid implicit data loss.


## Integrating Upscheme

After you've installed the `aimeos/upscheme` package using composer, you can use the `Up` class to execute your migration tasks:

```php
$config = [
	'driver' => 'pdo_mysql',
	'host' => '127.0.0.1',
	'dbname' => '<database>',
	'user' => '<dbuser>',
	'password' => '<secret>'
];

\Aimeos\Upscheme\Up::use( $config, 'src/migrations' )->up();
```

The `Up::use()` method requires two parameters: The database configuration and the path(s) to the migration tasks. For the config, the array keys and the values for `driver` must be supported by Doctrine DBAL. Available drivers are:

- pdo_mysql
- pdo_sqlite
- pdo_pgsql
- pdo_oci
- oci8
- ibm_db2
- pdo_sqlsrv
- mysqli
- drizzle_pdo_mysql
- sqlanywhere
- sqlsrv

If you didn't use Doctrine DBAL before, your database configuration may have a different structure and/or use different values for the database type. Upscheme allows you to register a custom method that transforms your configration into valid DBAL settings, e.g.:

```php
\Aimeos\Upscheme\Up::macro( 'createConnection', function( array $cfg ) {
	return [
		'driver' => $config['adapter'] !== 'mysql' ? $config['adapter'] : 'pdo_mysql',
		'host' => $config['host'],
		'dbname' => $config['database'],
		'user' => $config['username'],
		'password' => $config['secret']
	];
} );
```

Upscheme also supports several database connections which you can distinguish by their key name:

```php
$config = [
	'db' => [
		'driver' => 'pdo_mysql',
		'host' => '127.0.0.1',
		'dbname' => '<database>',
		'user' => '<dbuser>',
		'password' => '<secret>'
	],
	'temp' => [
		'driver' => 'pdo_sqlite',
		'path' => '/tmp/mydb.sqlite'
	]
];

\Aimeos\Upscheme\Up::use( $config, 'src/migrations' )->up();
```

Of course, you can also pass several migration paths to the `Up` class:

```php
\Aimeos\Upscheme\Up::use( $config, ['src/migrations', 'ext/migrations'] )->up();
```

To enable (debugging) output, use the verbose() method:

```php
\Aimeos\Upscheme\Up::use( $config, 'src/migrations' )->verbose()->up(); // most important only
\Aimeos\Upscheme\Up::use( $config, 'src/migrations' )->verbose( 'vv' )->up(); // more verbose
\Aimeos\Upscheme\Up::use( $config, 'src/migrations' )->verbose( 'vvv' )->up(); // debugging
```

## Writing migrations

A migration task only requires implementing the `up()` method and must be stored in one of the directories passed to the `Up` class:

```php
<?php

namespace Aimeos\Upscheme\Task;
use Aimeos\Upscheme\Schema\Table;


class TestTable extends Base
{
	public function up()
	{
		$this->db()->table( 'test', function( Table $t ) {
			$t->id();
			$t->string( 'label' );
			$t->bool( 'status' );
		} );
	}
}
```

The file your class is stored in must have the same name (case sensitive) as the class itself and the `.php` suffix, e.g:

```
class TestTable -> TestTable.php
```

There's no strict convention how to name migration task classes. You can either name them by what they do (e.g. "CreateTestTable"), what they operate on (e.g. "TestTable") or even use a timestamp (e.g. "20201231_Test"). If the tasks doesn't contain dependencies, they are sorted and executed in in alphabethical order and the sorting would be:

```
20201231_Test
CreateTestTable
TestTable
```

In your PHP file, always include the `namespace` statement first. The `use` statement is optional and only needed as shortcut for the type hint for the closure function argument. Your class also has to extend from the "Base" class or implement the ["Iface" interface](https://github.com/aimeos/upscheme/blob/master/src/Task/Iface.php).

### Dependencies

To specify dependencies to other migration tasks, use the `before()` and `after()` methods. `before()` must return the class names of the migration tasks which must be executed before your task while the `after()` method must return the class names which should be executed after your task (the post-dependencies):

```php
class TestTable extends Base
{
	public function before() : array
	{
		return ['CreateRefTable'];
	}

	public function after() : array
	{
		return ['InsertTestData'];
	}
}
```

Thus, the order of execution would be:

```
CreateRefTable -> TestTable -> InsertTestData
```

### Schemas

In the `up()` method, you have access to the database schema using the `db()` method. In case you've passed more than one database configuration to `Up::use()`, you can access the different schemas by their configuration key:

```php
// $config = ['db' => [...], 'temp' => [...]];
// \Aimeos\Upscheme\Up::use( $config, '...' )->up();

$this->db();
$this->db( 'db' );
$this->db( 'temp' );
```

If you pass no config key or one that doesn't exist, the first configuration is returned ("db" in this case). By using the available methods of the database schema object, you can add, update or drop tables, columns, indexes and other database objects. Also, you can use `insert()`, `select()`, `update()`, `delete()` and `stmt()` to manipulate the records of the tables.

After each migration task, the schema updates made in the task are automatically applied to the database. If you need to persist a change immediately because you want to insert data, call `$this->db()->up()` yourself. An `up()` method is also in any table, sequence, and column object available so you can call `up()` also there.

In cases you need two different database connections because you want to execute SELECT and INSERT/UPDATE/DELETE statements at the same time, pass `true` as second parameter to `db()` to get the database schema including a new connection:

```php
$db = $this->db( 'db', true );
```

All schema changes made are applied to the database before the schema with the new connection is returned. To avoid database connections to pile up until the database server rejects new connections, always calll `close()` for new connections created by `db( '<name>', true )`:

```php
$db->close();
```

### Messages

To output messages in your migration task use the `info()` method:

```php
$this->info( 'some message' );
$this->info( 'more verbose message', 'vv' );
$this->info( 'very verbose debug message', 'vvv' );
```

The second parameter is the verbosity level and none or `v` are standard messages, `vv` are messages that are only displayed if more verbosity is wanted while `vvv` is for debugging messages. There's also a third parameter for indenting the messages:

```php
$this->info( 'some message' );
$this->info( 'second level message', 'v', 1 );
$this->info( 'third level message', 'v', 2 );
```

This will display:

```
some message
  second level message
    third level message
```

Prerequisite is that the `verbose()` method of the `Up` class has been called before:

```php
\Aimeos\Upscheme\Up::use( $config, '...' )->verbose()->up();
```

## Schema objects

<nav>

### [DB](#database)

<ul class="method-list">
	<li><a href="#db__call">__call()</a></li>
	<li><a href="#dbclose">close()</a></li>
	<li><a href="#dbdelete">delete()</a></li>
	<li><a href="#dbdropColumn">dropColumn()</a></li>
	<li><a href="#dbdropForeign">dropForeign()</a></li>
	<li><a href="#dbdropIndex">dropIndex()</a></li>
	<li><a href="#dbdropSequence">dropSequence()</a></li>
	<li><a href="#dbdropTable">dropTable()</a></li>
	<li><a href="#dbfor">for()</a></li>
	<li><a href="#dbhasColumn">hasColumn()</a></li>
	<li><a href="#dbhasForeign">hasForeign()</a></li>
	<li><a href="#dbhasIndex">hasIndex()</a></li>
	<li><a href="#dbhasSequence">hasSequence()</a></li>
	<li><a href="#dbhasTable">hasTable()</a></li>
	<li><a href="#dbinsert">insert()</a></li>
	<li><a href="#dblastId">lastId()</a></li>
	<li><a href="#dbselect">select()</a></li>
	<li><a href="#dbsequence">sequence()</a></li>
	<li><a href="#dbstmt">stmt()</a></li>
	<li><a href="#dbtable">table()</a></li>
	<li><a href="#dbtype">type()</a></li>
	<li><a href="#dbup">up()</a></li>
	<li><a href="#dbupdate">update()</a></li>
</ul>

</nav>

### Database

The database scheme object you get by calling `$this->db()` in your migration task gives you full access to the current schema including all tables, sequences and other schema objects, e.g.:

```php
$table = $this->db()->table( 'test' );
$seq = $this->db()->sequence( 'seq_test' );
```

#### DB::__call()

Calls custom methods or passes unknown method calls to the Doctrine schema object

```php
public function __call( string $method, array $args )
```

* @param string `$method` Name of the method
* @param array `$args` Method parameters
* @return mixed Return value of the called method

**Examples:**

You can register custom methods that have access to the class properties of the Upscheme DB object:

```php
\Aimeos\Upscheme\Schema\DB::macro( 'hasFkIndexes', function( $val ) {
	return $this->to->hasExplicitForeignKeyIndexes();
} );

$db->hasFkIndexes();
```

Available class properties are:

; `$this->from`
: Original Doctrine database schema representing the current database

; `$this->to`
: Doctrine database schema containing the changes made up to now

; `$this->conn`
: Doctrine database connection

; `$this->up`
: Upscheme object

Furthermore, you can call any [Doctrine schema](https://github.com/doctrine/dbal/blob/3.1.x/src/Schema/Schema.php) method directly, e.g.:

```php
$db->hasExplicitForeignKeyIndexes();
```


#### DB::close()

Closes the database connection

```php
public function close()
```

Call `close()` only for DB schema objects created with `$this->db( '...', true )`. Otherwise, you will close the main connection and DBAL has to reconnect to the server which will degrade performance!

**Examples:**

```php
$db = $this->db( 'temp', true );
$db->dropTable( 'test' );
$db->close();
```


#### DB::delete()

Deletes the records from the given table

```php
public function delete( string $table, array $conditions = null ) : self
```

* @param string `$table` Name of the table
* @param array|null `$conditions` Key/value pairs of column names and value to compare with
* @return self Same object for fluid method calls

Warning: The condition values are escaped but the table name and condition
column names are not! Only use fixed strings for table name and condition
column names but no external input!

**Examples:**

```php
$db->delete( 'test', ['status' => false, 'type' => 'old'] );
$db->delete( 'test' );
```

Several conditions passed in the second parameter are combined by "AND". If you need more complex statements, use the [stmt()](#DB::stmt()) method instead.


#### DB::dropColumn()

Drops the column given by its name if it exists

```php
public function dropColumn( string $table, $name ) : self
```

* @param string `$table` Name of the table the column belongs to
* @param array|string `$name` Name of the column or columns
* @return self Same object for fluid method calls

**Examples:**

```php
$db->dropColumn( 'test', 'oldcol' );
$db->dropColumn( 'test', ['oldcol', 'oldcol2'] );
```

If the column or one of the columns doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.


#### DB::dropForeign()

Drops the foreign key constraint given by its name if it exists

```php
public function dropForeign( string $table, $name ) : self
```

* @param string `$table` Name of the table the foreign key constraint belongs to
* @param array|string `$name` Name of the foreign key constraint or constraints
* @return self Same object for fluid method calls

**Examples:**

```php
$db->dropForeign( 'test', 'fk_old' );
$db->dropForeign( 'test', ['fk_old', 'fk_old2'] );
```

If the foreign key constraint or one of the constraints doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.


#### DB::dropIndex()

Drops the index given by its name if it exists

```php
public function dropIndex( string $table, $name ) : self
```

* @param string `$table` Name of the table the index belongs to
* @param array|string `$name` Name of the index or indexes
* @return self Same object for fluid method calls

**Examples:**

```php
$db->dropIndex( 'test', 'idx_old' );
$db->dropIndex( 'test', ['idx_old', 'idx_old2'] );
```

If the index or one of the indexes doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.


#### DB::dropSequence()

Drops the sequence given by its name if it exists

```php
public function dropSequence( $name ) : self
```

* @param array|string `$name` Name of the sequence or sequences
* @return self Same object for fluid method calls

**Examples:**

```php
$db->dropSequence( 'seq_old' );
$db->dropSequence( ['seq_old', 'seq_old2'] );
```

If the sequence or one of the sequences doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.


#### DB::dropTable()

Drops the table given by its name if it exists

```php
public function dropTable( $name ) : self
```

* @param array|string $name Name of the table or tables
* @return self Same object for fluid method calls

**Examples:**

```php
$db->dropTable( 'test' );
$db->dropTable( ['test', 'test2'] );
```

If the table or one of the tables doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.


#### DB::for()

Executes a custom SQL statement if the database is of the given database platform type

```php
public function for( $for, $sql ) : self
```

* @param array|string `$type` Database type the statement should be executed for
* @param array|string `$sql` Custom SQL statement or statements
* @return self Same object for fluid method calls

Available database platform types are:

- mysql
- postgresql
- sqlite
- mssql
- oracle
- db2

The database changes are not applied immediately so always call `up()`
before executing custom statements to make sure that the tables you want
to use has been created before!

**Examples:**

```php
$db->for( 'mysql', 'CREATE INDEX idx_test_label ON test (label(16))' );

$db->for( ['mysql', 'sqlite'], [
	'DROP INDEX unq_test_status',
	'UPDATE test SET status = 0 WHERE status IS NULL',
] );
```


#### DB::hasColumn()

Checks if the column or columns exists

```php
public function hasColumn( string $table, $name ) : bool
```

* @param string `$table` Name of the table the column belongs to
* @param array|string `$name` Name of the column or columns
* @return TRUE if the columns exists, FALSE if not

**Examples:**

```php
$db->hasColumn( 'test', 'testcol' );
$db->hasColumn( 'test', ['testcol', 'testcol2'] );
```


#### DB::hasForeign()

Checks if the foreign key constraints exists

```php
public function hasForeign( string $table, $name ) : bool
```

* @param string `$table` Name of the table the foreign key constraint belongs to
* @param array|string `$name` Name of the foreign key constraint or constraints
* @return TRUE if the foreign key constraint exists, FALSE if not

**Examples:**

```php
$db->hasForeign( 'test', 'fk_testcol' );
$db->hasForeign( 'test', ['fk_testcol', 'fk_testcol2'] );
```


#### DB::hasIndex()

Checks if the indexes exists

```php
public function hasIndex( string $table, $name ) : bool
```

* @param string `$table` Name of the table the index belongs to
* @param array|string `$name` Name of the index or indexes
* @return TRUE if the index exists, FALSE if not

**Examples:**

```php
$db->hasIndex( 'test', 'idx_test_col' );
$db->hasIndex( 'test', ['idx_test_col', 'idx_test_col2'] );
```


#### DB::hasSequence()

Checks if the sequences exists

```php
public function hasSequence( $name ) : bool
```

* @param array|string `$name` Name of the sequence or sequences
* @return TRUE if the sequence exists, FALSE if not

**Examples:**

```php
$db->hasSequence( 'seq_test' );
$db->hasSequence( ['seq_test', 'seq_test2'] );
```


#### DB::hasTable()

Checks if the tables exists

```php
public function hasTable( $name ) : bool
```

* @param array|string `$name` Name of the table or tables
* @return TRUE if the table exists, FALSE if not

**Examples:**

```php
$db->hasTable( 'test' );
$db->hasTable( ['test', 'test2'] );
```


#### DB::insert()

Inserts a record into the given table

```php
	public function insert( string $table, array $data ) : self
```

* @param string `$table` Name of the table
* @param array `$data` Key/value pairs of column name/value to insert
* @return self Same object for fluid method calls

Warning: The data values are escaped but the table name and column names are not!
Only use fixed strings for table name and column names but no external input!

**Examples:**

```php
$db->insert( 'test', ['label' => 'myvalue', 'status' => true] );
```


#### DB::lastId()

Returns the ID of the last inserted row into any database table

```php
public function lastId( string $seq = null ) : string
```

* @param string|null `$seq` Name of the sequence generating the ID
* @return string Generated ID from the database

**Examples:**

```php
$db->lastId();
$db->lastId( 'seq_test' );
```


#### DB::select()

Returns the records from the given table

```php
public function select( string $table, array $conditions = null ) : array
```

* @param string `$table` Name of the table
* @param array|null `$conditions` Key/value pairs of column names and value to compare with
* @return array List of associative arrays containing column name/value pairs

Warning: The condition values are escaped but the table name and condition
column names are not! Only use fixed strings for table name and condition
column names but no external input!

**Examples:**

```php
$db->select( 'test', ['status' => false, 'type' => 'old'] );
$db->select( 'test' );
```

Several conditions passed in the second parameter are combined by "AND". If you need more complex statements, use the [stmt()](#DB::stmt()) method instead.


#### DB::sequence()

Returns the sequence object for the given name

```php
public function sequence( string $name, \Closure $fcn = null ) : Sequence
```

* @param string `$name` Name of the sequence
* @param \Closure|null `$fcn` Anonymous function with ($sequence) parameter creating or updating the sequence definition
* @return \Aimeos\Upscheme\Schema\Sequence Sequence object

If the sequence doesn't exist yet, it will be created. Passing a closure to modify the sequence will also persist the changes in the database automatically.

**Examples:**

```php
$sequence = $db->sequence( 'seq_test' );

$sequence = $db->sequence( 'seq_test', function( $seq ) {
	$seq->start( 1000 )->step( 2 )->cache( 100 );
} );
```


#### DB::stmt()

Returns the query builder for a new SQL statement

```php
public function stmt() : \Doctrine\DBAL\Query\QueryBuilder
```

* @return \Doctrine\DBAL\Query\QueryBuilder Query builder object

**Examples:**

```php
$db->stmt()->delete( 'test' )->where( 'status = ?' )->setParameter( 0, false );
$db->stmt()->select( 'id', 'label' )->from( 'test' );
$db->stmt()->update( 'test' )->set( 'status', '?' )->setParameter( 0, true );
```

For more details about the available Doctrine QueryBuilder methods, please have a look at the [Doctrine documentation](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html#building-a-query).


#### DB::table()

Returns the table object for the given name

```php
public function table( string $name, \Closure $fcn = null ) : Table
```

* @param string `$name` Name of the table
* @param \Closure|null `$fcn` Anonymous function with ($table) parameter creating or updating the table definition
* @return \Aimeos\Upscheme\Schema\Table Table object

If the table doesn't exist yet, it will be created. Passing a closure to modify the table will also persist the changes in the database automatically.

**Examples:**

```php
$table = $db->table( 'test' );

$table = $db->table( 'test', function( $t ) {
	$t->id();
	$t->string( 'label' );
	$t->bool( 'status' );
} );
```


#### DB::type()

Returns the type of the database

```php
public function type() : string
```

* @return string Database type

Possible values are:

* db2
* mssql
* mysql
* oracle
* postgresql
* sqlite

**Examples:**

```php
$type = $db->type();
```


#### DB::up()

Applies the changes to the database schema

```php
public function up() : self
```

* @return self Same object for fluid method calls

**Examples:**

```php
$db->up();
```


#### DB::update()

Updates the records from the given table

```php
public function update( string $table, array $data, array $conditions = null ) : self
```

* @param string `$table` Name of the table
* @param array `$data` Key/value pairs of column name/value to update
* @param array|null `$conditions` Key/value pairs of column names and value to compare with
* @return self Same object for fluid method calls

Warning: The condition and data values are escaped but the table name and
column names are not! Only use fixed strings for table name and condition
column names but no external input!

**Examples:**

```php
$db->update( 'test', ['status' => true] );
$db->update( 'test', ['status' => true], ['status' => false, 'type' => 'new'] );
```

Several conditions passed in the second parameter are combined by "AND". If you need more complex statements, use the [stmt()](#DB::stmt()) method instead.

