<a class="badge" href="https://github.com/aimeos/upscheme"><img src="https://circleci.com/gh/aimeos/aimeos-core.svg?style=shield" alt="Build Status" height="20"></a>
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
* [Integrating Upscheme](#integrating-upscheme)
* [Writing migrations](#writing-migrations)
  * [Dependencies](#dependencies)
  * [Messages](#messages)
  * [Schemas](#schemas)
* [Database](#database)
  * [Accessing objects](#accessing-objects)
  * [Checking existence](#checking-existence)
  * [Removing objects](#removing-objects)
  * [Query/modify table rows](#Query-modify-table-rows)
  * [Executing custom SQL](#executing-custom-sql)
  * [Database methods](#database-methods)
* [Tables](#tables)
  * [Creating tables](#creating-tables)
  * [Setting table options](#setting-table-options)
  * [Checking table existence](#checking-table-existence)
  * [Updating tables](#updating-tables)
  * [Dropping tables](#dropping-tables)
  * [Table methods](#table-methods)
* [Columns](#columns)
  * [Adding columns](#adding-columns)
  * [Available column types](#available-column-types)
  * [Column modifiers](#column-modifiers)
  * [Checking column existence](#checking-column-existence)
  * [Changing columns](#changing-columns)
  * [Dropping columns](#dropping-columns)
  * [Column methods](#column-methods)
* [Foreign keys](#foreign-keys)
  * [Methods](#forein-key-methods)
* [Sequences](#sequences)
  * [Methods](#sequence-methods)


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

To specify dependencies to other migration tasks, use the `after()` and `before()` methods. Your task is executed after the tasks returned by `after()` and before the tasks returned by `before()`:

```php
class TestTable extends Base
{
	public function after() : array
	{
		return ['CreateRefTable'];
	}

	public function before() : array
	{
		return ['InsertTestData'];
	}
}
```

Thus, the order of execution would be:

```
CreateRefTable -> TestTable -> InsertTestData
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

### Schemas

In the `up()` method, you have access to the database schema using the `db()` method. In case you've passed more than one database configuration to `Up::use()`, you can access the different schemas by their configuration key:

```php
// $config = ['db' => [...], 'temp' => [...]];
// \Aimeos\Upscheme\Up::use( $config, '...' )->up();

$this->db();
$this->db( 'db' );
$this->db( 'temp' );
```

If you pass no config key or one that doesn't exist, the first configuration is returned ("db" in this case). By using the available methods of the database schema object, you can add, update or drop tables, columns, indexes and other database objects. Also, you can use [insert()](#dbinsert), [select()](#dbselect), [update()](#dbupdate), [delete()](#dbdelete) and [stmt()](#dbstmt) to manipulate the records of the tables.

After each migration task, the schema updates made in the task are automatically applied to the database. If you need to persist a change immediately because you want to insert data, call `$this->db()->up()` yourself. The `up()` method is also available in any table, sequence, and column object so you can call `up()` everywhere.

In cases you need two different database connections because you want to execute SELECT and INSERT/UPDATE/DELETE statements at the same time, pass `true` as second parameter to `db()` to get the database schema including a new connection:

```php
$db1 = $this->db();
$db2 = $this->db( 'db', true );

foreach( $db1->select( 'users', ['status' => false] ) as $row ) {
	$db2->insert( 'oldusers', $row );
}

$db2->delete( 'users', ['status' => false] );
```

All schema changes made are applied to the database before the schema with the new connection is returned. To avoid database connections to pile up until the database server rejects new connections, always calll `close()` for new connections created by `db( '<name>', true )`:

```php
$db2->close();
```


## Database

### Accessing objects

You get the database schema object in your task by calling `$this->db()` as described in the [schema section](#schemas). It gives you full access to the database schema including all tables, sequences and other schema objects:

```php
$table = $this->db()->table( 'users' );
$seq = $this->db()->sequence( 'seq_users' );
```

If the table or seqence doesn't exist, it will be created. Otherwise, the existing table or sequence object is returned. In both cases, you can modify the objects afterwards and add e.g. new columns to the table.

### Checking existence

You can test for tables, columns, indexes, foreign keys and sequences using the database schema returned by `$this->db()`:

```php
$db = $this->db();

if( $db->hasTable( 'users' ) {
    // The "users" table exists
}

if( $db->hasColumn ( 'users', 'name' ) {
    // The "name" column in the "users" table exists
}

if( $db->hasIndex( 'users', 'idx_name' ) {
    // The "idx_name" index in the "users" table exists
}

if( $db->hasForeign( 'users_address', 'fk_users_id' ) {
    // The foreign key "fk_users_id" in the "users_address" table exists
}

if( $db->hasSequence( 'seq_users' ) {
    // The "seq_users" sequence exists
}
```

### Removing objects

The database object returned by `$this->db()` also has methods for dropping tables, columns, indexes, foreign keys and sequences:

```php
$db = $this->db();

// Drops the foreign key "fk_users_id" from the "users_address" table
$db->dropForeign( 'users_address', 'fk_users_id' );

// Drops the "idx_name" index from the "users" table
$db->dropIndex( 'users', 'idx_name' );

// Drops the "name" column from the "users" table
$db->dropColumn ( 'users', 'name' );

// Drops the "seq_users" sequence
$db->dropSequence( 'seq_users' );

// Drops the "users" table
$db->dropTable( 'users' );
```

If the table, column, index, foreign key or sequence doesn't exist, it is silently ignored. For cases where you need to know if they exist, use the [hasTable()](#dbhastable), [hasColumn()](#dbhascolumn), [hasIndex()](#dbhasindex), [hasForeign()](#dbhasforeign) and [hasSeqence()](#dbhassequence) methods before like described in the ["Checking for existence"](#checking-for-existence) section.

### Query and modify table rows

The [insert()](#dbinsert), [select()](#dbselect), [update()](#dbupdate) and [delete()](#dbdelete) methods are an easy way to add, retrieve, modify and remove rows in any table:

```php
$db1 = $this->db();
$db2 = $this->db( 'db', true );

foreach( $db1->select( 'users', ['status' => false] ) as $row )
{
	$db2->insert( 'newusers', ['userid' => $row['id'], 'status' => true] );
	$db2->update( 'users', ['refid' => $db2->lastId()], ['id' => $row['id']] );
}

$db2->delete( 'newusers', ['status' => false] );
$db2->close();
```

If you use `select()` simultaniously with `insert()`, `update()` or `delete()`, you must create a second database connection because the `select()` statement will return rows while you send new commands to the database server. This only works on separate connections, not on the same.

You can only pass simple key/value pairs for conditions to the methods which are combined by AND. If you need more complex queries, use the [stmt()](#dbstmt) instead:

```php
$db = $this->db();

$db->stmt()->select( 'id', 'name' )
	->from( 'users' )
	->where( 'status != ?' )
	->setParameter( 0, false );

$db->stmt()->delete( 'users' )
	->where( 'status != ?' )
	->setParameter( 0, false );

$db->stmt()->update( 'users' )
	->set( 'status', '?' )
	->where( 'status != ?' )
	->setParameters( [true, false] );
```

The `stmt()` method returns a `Doctrine\DBAL\Query\QueryBuilder` object which enables you to build more advanced statement. Please have a look into the [Doctrine Query Builder](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html) documentation for more details.

### Executing custom SQL

Doctrine only supports a common subset of SQL statements and not all possibilities the database vendors have implemented. To remove that limit, Upscheme offers the [for()](#dbfor) method to execute custom SQL statements not supported by Doctrine DBAL. The method will execute the statement only for the database platform or platforms named in the first parameter:

```php
$db = $this->db();

if( !$db->hasIndex( 'product', 'idx_text' ) ) {
	$db->for( 'mysql', 'CREATE FULLTEXT INDEX idx_text ON product (text)' );
}
```

This is especially useful for creating special types of indexes where the syntax differs between the database implementations.

### Database methods

<nav>
<div class="method-header"><a href="#database">Database</a></div>
<ul class="method-list">
	<li><a href="#db__call">__call()</a></li>
	<li><a href="#dbclose">close()</a></li>
	<li><a href="#dbdelete">delete()</a></li>
	<li><a href="#dbdropcolumn">dropColumn()</a></li>
	<li><a href="#dbdropforeign">dropForeign()</a></li>
	<li><a href="#dbdropindex">dropIndex()</a></li>
	<li><a href="#dbdropsequence">dropSequence()</a></li>
	<li><a href="#dbdroptable">dropTable()</a></li>
	<li><a href="#dbfor">for()</a></li>
	<li><a href="#dbhascolumn">hasColumn()</a></li>
	<li><a href="#dbhasforeign">hasForeign()</a></li>
	<li><a href="#dbhasindex">hasIndex()</a></li>
	<li><a href="#dbhassequence">hasSequence()</a></li>
	<li><a href="#dbhastable">hasTable()</a></li>
	<li><a href="#dbinsert">insert()</a></li>
	<li><a href="#dblastid">lastId()</a></li>
	<li><a href="#dbselect">select()</a></li>
	<li><a href="#dbsequence">sequence()</a></li>
	<li><a href="#dbstmt">stmt()</a></li>
	<li><a href="#dbtable">table()</a></li>
	<li><a href="#dbtype">type()</a></li>
	<li><a href="#dbup">up()</a></li>
	<li><a href="#dbupdate">update()</a></li>
</ul>
</nav>


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

`$this->from`
: Original Doctrine database schema representing the current database

`$this->to`
: Doctrine database schema containing the changes made up to now

`$this->conn`
: Doctrine database connection

`$this->up`
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
* @param array&#124;null `$conditions` Key/value pairs of column names and value to compare with
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
* @param array&#124;string `$name` Name of the column or columns
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
* @param array&#124;string `$name` Name of the foreign key constraint or constraints
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
* @param array&#124;string `$name` Name of the index or indexes
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

* @param array&#124;string `$name` Name of the sequence or sequences
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

* @param array&#124;string $name Name of the table or tables
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

* @param array&#124;string `$type` Database type the statement should be executed for
* @param array&#124;string `$sql` Custom SQL statement or statements
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
* @param array&#124;string `$name` Name of the column or columns
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
* @param array&#124;string `$name` Name of the foreign key constraint or constraints
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
* @param array&#124;string `$name` Name of the index or indexes
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

* @param array&#124;string `$name` Name of the sequence or sequences
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

* @param array&#124;string `$name` Name of the table or tables
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

* @param string&#124;null `$seq` Name of the sequence generating the ID
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
* @param array&#124;null `$conditions` Key/value pairs of column names and value to compare with
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
* @param \Closure&#124;null `$fcn` Anonymous function with ($sequence) parameter creating or updating the sequence definition
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
* @param \Closure&#124;null `$fcn` Anonymous function with ($table) parameter creating or updating the table definition
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
* @param array&#124;null `$conditions` Key/value pairs of column names and value to compare with
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



## Tables

### Creating tables

The table scheme object you get by calling `$db->table( '<table name>' )` in your migration task gives you full access to the table and you can add, change or remove columns, indexes and foreign keys, e.g.:

```php
$this->db()->table( 'test', function( $table ) {
	$table->id();
	$table->string( 'label' );
	$table->col( 'status', 'tinyint' )->default( 0 );
} );
```

Besides the [`col()`](#tablecol) method which can add columns of arbitrary types, there are some shortcut methods for types available in all database server implementations:

| Column type | Description |
|-------------|-------------|
| [bigid](#tableid) | BIGINT column with a sequence/autoincrement and a primary key assigned |
| [bigint](#tablebigint) | BIGINT column with a range from −9223372036854775808 to 9223372036854775807 |
| [binary](#tablebinary) | VARBINARY column with up to 255 bytes |
| [blob](#tableblob) | BLOB column with up to 2GB |
| [bool](#tablebool) | BOOLEAN/BIT/NUMBER colum, alias for "boolean" |
| [boolean](#tableboolean) | BOOLEAN/BIT/NUMBER colum for TRUE/FALSE resp. 0/1 values |
| [date](#tabledate) | DATE column in ISO date format ("YYYY-MM-DD) without time and timezone |
| [datetime](#tabledatetime) | DATETIME column in ISO date/time format ("YYYY-MM-DD HH:mm:ss" ) |
| [tablesdatetimetz](#tabledatetimetz) | DATETIMETZ column in ISO date/time format but with varying timezone format |
| [decimal](#tabledecimal) | DECIMAL column for numeric data with fixed-point precision (string in PHP) |
| [float](#tablefloat) | FLOAT column for numeric data with a 8-byte floating-point precision |
| [guid](#tableguid) | Globally unique identifier with 36 bytes |
| [id](#tableid) | INTEGER column with a sequence/autoincrement and a primary key assigned |
| [int](#tableint) | INTEGER colum, alias for "integer" |
| [integer](#tableinteger) | INTEGER colum with a range from −2147483648 to 2147483647 |
| [json](#tablejson) | JSON column for UTF-8 encoded JSON data |
| [smallint](#tablesmallint) | INTEGER colum with a range from −32768 to 32767 |
| [string](#tablestring) | VARCHAR column with up to 255 characters |
| [text](#tabletext) | TEXT/CLOB column with up to 2GB characters |
| [time](#tabletime) | TIME column in 24 hour "HH:MM" fromat, e.g. "05:30" or "22:15" |
| [uuid](#tableuuid) | Globally unique identifier with 36 bytes, alias for "guid" |

### Setting table options

MySQL (or MariaDB, etc.) supports a few options to define aspects of the table. The *engine*
option will specify the storage engine used for the table:

```php
$this->db()->table( 'test', function( $table ) {
	$table->opt( 'engine', 'InnoDB' );
} );
```

As a shortcut, it's also possible to set the option as property:

```php
$this->db()->table( 'test', function( $table ) {
	$table->engine = 'InnoDB';
} );
```

To create a *temporary* table, use:

```php
$this->db()->table( 'test', function( $table ) {
	$table->engine = true;
} );
```


It's also possible to set the default *charset* and *collation* for string and text columns:

```php
$this->db()->table( 'test', function( $table ) {
    $table->charset = 'utf8mb4';
    $table->collation = 'utf8mb4_unicode_ci';
} );
```

**Note:** Collations are also supported by PostgreSQL and SQL Server but their values
are different. Thus, it's not possible to use the same value for all server types. To
circumvent that problem, use the column [`opt()`](#columnopt) method and pass the database
server type as third parameter:

```php
$this->db()->table( 'test', function( $table ) {
    $table->opt( 'charset', 'utf8mb4', 'mysql' );
    $table->opt( 'collation', 'utf8mb4_unicode_ci', 'mysql' );
} );
```

Now, the default *charset* and *collation* will be only set for MySQL database servers
(or MariaDB and similar forks).

### Checking table existence

To check if a table already exists, use the [`hasTable()`](#dbhastable) method:

```php
if( $this->db()->hasTable( 'users' ) {
    // The "users" table exists
}
```

You can check for several tables at once too:

```php
if( $this->db()->hasTable( ['users', 'addresses'] ) {
    // The "users" and "addresses" tables exist
}
```

The [`hasTable()`(#dbhastable) method will only return `TRUE` if all tables exist.

### Updating tables

Besides creating and accessing tables, the [`table()`](#dbtable) method from the schema object
can be used to update a table schema too. It accepts the table name and a closure
that will receive the table schema object.

Let's create a table named *test* first including three columns:

```php
$this->db()->table( 'test', function( $table ) {
	$table->id();
	$table->string( 'label' );
	$table->col( 'status', 'tinyint' )->default( 0 );
} );
```

Now, we want to update the table in another migration by adding a *code* column and
changing the default value of the existing *status* column:

```php
$this->db()->table( 'test', function( $table ) {
	$table->string( 'code' );
	$table->col( 'status', 'tinyint' )->default( 1 );
} );
```

The changes will be persisted in the database as soon as the [`table()`](#dbtable) method
returns so there's no need to call [`up()`](#dbup) yourself afterwards. For the available
column types and options, refer to the [columns section](#columns).

### Dropping tables

To remove a table, you should use the [`dropTable()`](#dbdroptable) method from the database schema:

```php
$this->db()->dropTable( 'users' );
```

You can also drop several tables at once by passing the list as array:

```php
$this->db()->dropTable( ['users', 'addresses'] );
```

Tables are only removed if they exist. If a table doesn't exist any more, no error is reported:

```php
$this->db()->dropTable( 'notexist' );
```

In that case, the method call will succeed but nothing will happen.

### Table methods

<nav>
<div class="method-header"><a href="#tables">Tables</a></div>
<ul class="method-list">
	<li><a href="#table__call">__call()</a></li>
	<li><a href="#table__get">__get()</a></li>
	<li><a href="#table__set">__set()</a></li>
	<li><a href="#tablebigid">bigid()</a></li>
	<li><a href="#tablebigint">bigint()</a></li>
	<li><a href="#tablebinary">binary()</a></li>
	<li><a href="#tableblob">blob()</a></li>
	<li><a href="#tablebool">bool()</a></li>
	<li><a href="#tableboolean">boolean()</a></li>
	<li><a href="#tablecol">col()</a></li>
	<li><a href="#tabledate">date()</a></li>
	<li><a href="#tabledatetime">datetime()</a></li>
	<li><a href="#tabledatetimetz">datetimetz()</a></li>
	<li><a href="#tabledecimal">decimal()</a></li>
	<li><a href="#tabledropcolumn">dropColumn()</a></li>
	<li><a href="#tabledropindex">dropIndex()</a></li>
	<li><a href="#tabledropforeign">dropForeign()</a></li>
	<li><a href="#tabledropprimary">dropPrimary()</a></li>
	<li><a href="#tablefloat">float()</a></li>
	<li><a href="#tableforeign">foreign()</a></li>
	<li><a href="#tableguid">guid()</a></li>
	<li><a href="#tablehascolumn">hasColumn()</a></li>
	<li><a href="#tablehasindex">hasIndex()</a></li>
	<li><a href="#tablehasforeign">hasForeign()</a></li>
	<li><a href="#tableid">id()</a></li>
	<li><a href="#tableindex">index()</a></li>
	<li><a href="#tableint">int()</a></li>
	<li><a href="#tableinteger">integer()</a></li>
	<li><a href="#tablejson">json()</a></li>
	<li><a href="#tablename">name()</a></li>
	<li><a href="#tableopt">opt()</a></li>
	<li><a href="#tableprimary">primary()</a></li>
	<li><a href="#tablerenameindex">renameIndex()</a></li>
	<li><a href="#tablesmallint">smallint()</a></li>
	<li><a href="#tablespatial">spatial()</a></li>
	<li><a href="#tablestring">string()</a></li>
	<li><a href="#tabletext">text()</a></li>
	<li><a href="#tabletime">time()</a></li>
	<li><a href="#tableunique">unique()</a></li>
	<li><a href="#tableuuid">uuid()</a></li>
	<li><a href="#tableup">up()</a></li>
</ul>
</nav>


#### Table::__call()

Calls custom methods or passes unknown method calls to the Doctrine table object

```php
public function __call( string $method, array $args )
```

* @param string `$method` Name of the method
* @param array `$args` Method parameters
* @return mixed Return value of the called method

**Examples:**

You can register custom methods that have access to the class properties of the Upscheme Table object:

```php
\Aimeos\Upscheme\Schema\Table::macro( 'addConstraint', function( array $columns ) {
	return $this->to->addUniqueConstraint( $columns );
} );

$table->addConstraint( ['col1', 'col2'] );
```

Available class properties are:

`$this->table`
: Doctrine table schema

`$this->up`
: Upscheme object

Furthermore, you can call any [Doctrine table](https://github.com/doctrine/dbal/blob/3.1.x/src/Schema/Table.php) method directly, e.g.:

```php
$table->addUniqueConstraint( ['col1', 'col2'] );
```

#### Table::__get()

Returns the value for the given table option

```php
public function __get( string $name )
```

* @param string `$name` Table option name
* @return mixed Table option value

The list of available table options are:

* charset (MySQL)
* collation (MySQL)
* engine (MySQL)
* temporary (MySQL)

**Examples:**

```php
$engine = $table->engine;

// same as
$engine = $table->opt( 'engine' );
```


#### Table::__set()

Sets the new value for the given table option

```php
public function __set( string $name, $value )
```

* @param string `$name` Table option name
* @param mixed Table option value

The list of available table options are:

* charset (MySQL)
* collation (MySQL)
* engine (MySQL)
* temporary (MySQL)

**Examples:**

```php
$table->engine = 'InnoDB';

// same as
$table->opt( 'engine', 'InnoDB' );
```


#### Table::bigid()

Creates a new ID column of type "bigint" or returns the existing one

```php
public function bigid( string $name = null ) : Column
```

* @param string&#124;null Name of the ID column
* @return \Aimeos\Upscheme\Schema\Column Column object

The column gets a sequence (autoincrement) and a primary key assigned automatically.
If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->bigid();
$table->bigid( 'uid' );
```


#### Table::bigint()

Creates a new column of type "bigint" or returns the existing one

```php
public function bigint( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->bigint( 'testcol' );
```


#### Table::binary()

Creates a new column of type "binary" or returns the existing one

```php
public function binary( string $name, int $length = 255 ) : Column
```

* @param string `$name` Name of the column
* @param int `$length` Length of the column in bytes
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->binary( 'testcol' );
$table->binary( 'testcol', 32 );
```


#### Table::blob()

Creates a new column of type "blob" or returns the existing one

```php
public function blob( string $name, int $length = 0x7fff ) : Column
```

* @param string `$name` Name of the column
* @param int `$length` Length of the column in bytes
* @return \Aimeos\Upscheme\Schema\Column Column object

The maximum length of a "blob" column is 2GB.
If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->blob( 'testcol' );
$table->blob( 'testcol', 0x7fffffff );
```


#### Table::bool()

Creates a new column of type "boolean" or returns the existing one

```php
public function bool( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

This method is an alias for boolean().
If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->bool( 'testcol' );
```


#### Table::boolean()

Creates a new column of type "boolean" or returns the existing one

```php
public function boolean( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->boolean( 'testcol' );
```


#### Table::col()

Creates a new column or returns the existing one

```php
public function col( string $name, string $type ) : Column
```

* @param string `$name` Name of the column
* @param string `$type` Type of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->col( 'testcol', 'tinyint' );
```


#### Table::date()

Creates a new column of type "date" or returns the existing one

```php
public function date( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->date( 'testcol' );
```


#### Table::datetime()

Creates a new column of type "datetime" or returns the existing one

```php
public function datetime( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->datetime( 'testcol' );
```


#### Table::datetimetz()

Creates a new column of type "datetimetz" or returns the existing one

```php
public function datetimetz( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->datetimetz( 'testcol' );
```


#### Table::decimal()

Creates a new column of type "decimal" or returns the existing one

```php
public function decimal( string $name, int $digits, int $decimals = 2 ) : Column
```

* @param string `$name` Name of the column
* @param int `$digits` Total number of decimal digits including decimals
* @param int `$decimals` Number of digits after the decimal point
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->decimal( 'testcol', 10 ); // 10 digits incl. 2 decimals
$table->decimal( 'testcol', 10, 4 ); // 10 digits incl. 4 decimals
```


#### Table::dropColumn()

Drops the column given by its name if it exists

```php
public function dropColumn( $name ) : self
```

* @param array&#124;string `$name` Name of the column or columns
* @return self Same object for fluid method calls

If the column or one of the columns doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.

**Examples:**

```php
$table->dropColumn( 'testcol' );
$table->dropColumn( ['testcol', 'testcol2'] );
```


#### Table::dropIndex()

Drops the index given by its name if it exists

```php
public function dropIndex( $name ) : self
```

* @param array&#124;string `$name` Name of the index or indexes
* @return self Same object for fluid method calls

If the index or one of the indexes doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.

**Examples:**

```php
$table->dropIndex( 'idx_test_col' );
$table->dropIndex( ['idx_test_col', 'idx_test_col2'] );
```


#### Table::dropForeign()

Drops the foreign key constraint given by its name if it exists

```php
public function dropForeign( $name ) : self
```

* @param array&#124;string `$name` Name of the foreign key constraint or constraints
* @return self Same object for fluid method calls

If the foreign key constraint or one of the constraints doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.

**Examples:**

```php
$table->dropForeign( 'fk_test_col' );
$table->dropForeign( ['fk_test_col', 'fk_test_col2'] );
```


#### Table::dropPrimary()

Drops the primary key if it exists

```php
public function dropPrimary() : self
```

* @return self Same object for fluid method calls

If the primary key doesn't exist, it will be silently ignored. The change won't be applied until the migration task finishes or `up()` is called.

**Examples:**

```php
$table->dropPrimary();
```


#### Table::float()

Creates a new column of type "float" or returns the existing one

```php
public function float( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->float( 'testcol' );
```


#### Table::foreign()

Creates a new foreign key or returns the existing one

```php
public function foreign( $localcolumn, string $foreigntable, $foreigncolumn = 'id', string $name = null ) : Foreign
```

* @param array&#124;string $localcolumn Name of the local column or columns
* @param string $foreigntable Name of the referenced table
* @param array&#124;string $localcolumn Name of the referenced column or columns
* @param string&#124;null Name of the foreign key constraint and foreign key index or NULL for autogenerated name
* @return \Aimeos\Upscheme\Schema\Foreign Foreign key constraint object

The length of the foreign key name shouldn't be longer than 30 characters for maximum compatibility.

**Examples:**

```php
$table->foreign( 'parentid', 'test' );
$table->foreign( 'parentid', 'test', 'uid' );
$table->foreign( 'parentid', 'test', 'id', 'fk_test_pid' );
$table->foreign( ['parentid', 'siteid'], 'test', ['uid', 'siteid'] );
```


#### Table::guid()

Creates a new column of type "guid" or returns the existing one

```php
public function guid( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->guid( 'testcol' );
```


#### Table::hasColumn()

Checks if the column exists

```php
public function hasColumn( $name ) : bool
```

* @param array&#124;string `$name` Name of the column or columns
* @return TRUE if the columns exists, FALSE if not

**Examples:**

```php
$table->hasColumn( 'testcol' );
$table->hasColumn( ['testcol', 'testcol2'] );
```


#### Table::hasIndex()

Checks if the index exists

```php
public function hasIndex( $name ) : bool
```

* @param array&#124;string `$name` Name of the index or indexes
* @return TRUE if the indexes exists, FALSE if not

**Examples:**

```php
$table->hasIndex( 'idx_test_col' );
$table->hasIndex( ['idx_test_col', 'idx_test_col2'] );
```


#### Table::hasForeign()

Checks if the foreign key constraint exists

```php
public function hasForeign( $name ) : bool
```

* @param array&#124;string `$name` Name of the foreign key constraint or constraints
* @return TRUE if the foreign key constraints exists, FALSE if not

**Examples:**

```php
$table->hasForeign( 'fk_test_col' );
$table->hasForeign( ['fk_test_col', 'fk_test_col2'] );
```


#### Table::id()

Creates a new ID column of type "integer" or returns the existing one

```php
public function id( string $name = null ) : Column
```

* @param string&#124;null Name of the ID column
* @return \Aimeos\Upscheme\Schema\Column Column object

The column gets a sequence (autoincrement) and a primary key assigned automatically.
If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->id();
$table->id( 'uid' );
```


#### Table::index()

Creates a new index or replaces an existing one

```php
public function index( $columns, string $name = null ) : self
```

* @param array&#124;string $columns Name of the columns or columns spawning the index
* @param string&#124;null $name Index name or NULL for autogenerated name
* @return self Same object for fluid method calls

The length of the index name shouldn't be longer than 30 characters for maximum compatibility.

**Examples:**

```php
$table->index( 'testcol' );
$table->index( ['testcol', 'testcol2'] );
$table->index( 'testcol', 'idx_test_testcol );
```


#### Table::int()

Creates a new column of type "integer" or returns the existing one

```php
public function int( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

This method is an alias for integer().
If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->int( 'testcol' );
```


#### Table::integer()

Creates a new column of type "integer" or returns the existing one

```php
public function integer( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->integer( 'testcol' );
```


#### Table::json()

Creates a new column of type "json" or returns the existing one

```php
public function json( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->json( 'testcol' );
```


#### Table::name()

Returns the name of the table

```php
public function name() : string
```

* @return string Table name

**Examples:**

```php
$tablename = $table->name();
```


#### Table::opt()

Sets a custom schema option or returns the current value

```php
public function opt( string $name, $value = null )
```

* @param string $name Name of the table-related custom schema option
* @param mixed $value Value of the custom schema option
* @return self&#124;mixed Same object for setting value, current value without second parameter

Available custom schema options are:

* charset (MySQL)
* collation (MySQL)
* engine (MySQL)
* temporary (MySQL)

**Examples:**

```php
$charset = $table->opt( 'charset' );
$table->opt( 'charset', 'utf8' )->opt( 'collation', 'utf8_bin' );

// Magic methods:
$charset = $table->charset;
$table->charset = 'binary';
```


#### Table::primary()

Creates a new primary index or replaces an existing one

```php
public function primary( $columns, string $name = null ) : self
```

* @param array&#124;string $columns Name of the columns or columns spawning the index
* @param string&#124;null $name Index name or NULL for autogenerated name
* @return self Same object for fluid method calls

The length of the index name shouldn't be longer than 30 characters for maximum compatibility.

**Examples:**

```php
$table->primary( 'testcol' );
$table->primary( ['testcol', 'testcol2'] );
$table->primary( 'testcol', 'pk_test_testcol' );
```


#### Table::renameIndex()

Renames an index or a list of indexes

```php
public function renameIndex( $from, string $to = null ) : self
```

* @param array&#124;string $from Index name or array of old/new index names (if new index name is NULL, it will be generated)
* @param string&#124;null $to New index name or NULL for autogenerated name (ignored if first parameter is an array)
* @return self Same object for fluid method calls

The length of the indexes name shouldn't be longer than 30 characters for maximum compatibility.

**Examples:**

```php
// generate a new name automatically
$table->renameIndex( 'test_col_index' );

// custom name
$table->renameIndex( 'test_col_index', 'idx_test_col' );

// rename several indexes at once
$table->renameIndex( ['test_col_index' => null, 'test_index' => 'idx_test_col'] );
```


#### Table::smallint()

Creates a new column of type "smallint" or returns the existing one

```php
public function smallint( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->smallint( 'testcol' );
```


#### Table::spatial()

Creates a new spatial index or replaces an existing one

```php
public function spatial( $columns, string $name = null ) : self
```

* @param array&#124;string $columns Name of the columns or columns spawning the index
* @param string&#124;null $name Index name or NULL for autogenerated name
* @return self Same object for fluid method calls

The length of the index name shouldn't be longer than 30 characters for maximum compatibility.

**Examples:**

```php
$table->spatial( 'testcol' );
$table->spatial( ['testcol', 'testcol2'] );
$table->spatial( 'testcol', 'idx_test_testcol' );
```


#### Table::string()

Creates a new column of type "string" or returns the existing one

```php
public function string( string $name, int $length = 255 ) : Column
```

* @param string `$name` Name of the column
* @param int `$length` Length of the column in characters
* @return \Aimeos\Upscheme\Schema\Column Column object

This type should be used for up to 255 characters. For more characters, use the "text" type.
If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->string( 'testcol' );
$table->string( 'testcol', 32 );
```


#### Table::text()

Creates a new column of type "text" or returns the existing one

```php
public function text( string $name, int $length = 0xffff ) : Column
```

* @param string `$name` Name of the column
* @param int `$length` Length of the column in characters
* @return \Aimeos\Upscheme\Schema\Column Column object

The maximum length of a "text" column is 2GB.
If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->text( 'testcol' );
$table->text( 'testcol', 0x7fffffff );
```


#### Table::time()

Creates a new column of type "time" or returns the existing one

```php
public function time( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->time( 'testcol' );
```


#### Table::unique()

Creates a new unique index or replaces an existing one

```php
public function unique( $columns, string $name = null ) : self
```

* @param array&#124;string $columns Name of the columns or columns spawning the index
* @param string&#124;null $name Index name or NULL for autogenerated name
* @return self Same object for fluid method calls

The length of the index name shouldn't be longer than 30 characters for maximum compatibility.

**Examples:**

```php
$table->unique( 'testcol' );
$table->unique( ['testcol', 'testcol2'] );
$table->unique( 'testcol', 'unq_test_testcol' );
```


#### Table::uuid()

Creates a new column of type "guid" or returns the existing one

```php
public function uuid( string $name ) : Column
```

* @param string `$name` Name of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

This method is an alias for guid().
If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->uuid( 'testcol' );
```


#### Table::up()

Applies the changes to the database schema

```php
public function up() : self
```

* @return self Same object for fluid method calls

**Examples:**

```php
$table->up();
```



## Columns

### Adding columns

The column schema object you get by calling `$table->col( '<name>', '<type>' )`
in your migration task gives you access to all column properties. There are also
shortcuts available for column type supported by all supported databases types.
Each column can be changed by one or more modifier methods and you can also add
indexes to single columns, e.g.:

```php
$this->db()->table( 'test', function( $table ) {
	$table->id()->unsigned( true );
	$table->string( 'label' )->index();
	$table->col( 'status', 'tinyint' )->default( 0 );
} );
```

The example will add the following columns:

* *id* of type integer with unsigend modifier
* *label* of type string with 255 chars and an index
* *status* of type tinyint (MySQL only) with a default value of zero

### Available column types

There are some shortcut methods for column types available in all database server
implementations:

| Column type | Description |
|-------------|-------------|
| [bigid](#tableid) | BIGINT column with a sequence/autoincrement and a primary key assigned |
| [bigint](#tablebigint) | BIGINT column with a range from −9223372036854775808 to 9223372036854775807 |
| [binary](#tablebinary) | VARBINARY column with up to 255 bytes |
| [blob](#tableblob) | BLOB column with up to 2GB |
| [bool](#tablebool) | BOOLEAN/BIT/NUMBER colum, alias for "boolean" |
| [boolean](#tableboolean) | BOOLEAN/BIT/NUMBER colum for TRUE/FALSE resp. 0/1 values |
| [date](#tabledate) | DATE column in ISO date format ("YYYY-MM-DD) without time and timezone |
| [datetime](#tabledatetime) | DATETIME column in ISO date/time format ("YYYY-MM-DD HH:mm:ss" ) |
| [tablesdatetimetz](#tabledatetimetz) | DATETIMETZ column in ISO date/time format but with varying timezone format |
| [decimal](#tabledecimal) | DECIMAL column for numeric data with fixed-point precision (string in PHP) |
| [float](#tablefloat) | FLOAT column for numeric data with a 8-byte floating-point precision |
| [guid](#tableguid) | Globally unique identifier with 36 bytes |
| [id](#tableid) | INTEGER column with a sequence/autoincrement and a primary key assigned |
| [int](#tableint) | INTEGER colum, alias for "integer" |
| [integer](#tableinteger) | INTEGER colum with a range from −2147483648 to 2147483647 |
| [json](#tablejson) | JSON column for UTF-8 encoded JSON data |
| [smallint](#tablesmallint) | INTEGER colum with a range from −32768 to 32767 |
| [string](#tablestring) | VARCHAR column with up to 255 characters |
| [text](#tabletext) | TEXT/CLOB column with up to 2GB characters |
| [time](#tabletime) | TIME column in 24 hour "HH:MM" fromat, e.g. "05:30" or "22:15" |
| [uuid](#tableuuid) | Globally unique identifier with 36 bytes, alias for "guid" |

To add database specific column types, use the [`col()`](#tablecol) method, e.g.:

```php
$this->db()->table( 'test', function( $table ) {
	$table->col( 'status', 'tinyint' );
} );
```

### Column modifiers

It's also possible to change column definitions by calling one or more column
modifier methods:

```php
$this->db()->table( 'test', function( $table ) {
	$table->int( 'number' )->null( true )->unsigned( true );
} );
```

The available column modifier methods are:

| Column modifier | Description |
|-----------------|-------------|
| [->autoincrement( true )](#columnautoincrement) | Set INTEGER columns as auto-incrementing (alias for [`seq()`](#columnseq)) |
| [->charset( 'utf8' )](#columncharset) | The character set used by the column (MySQL) |
| [->collation( 'binary' )](#columncollation) | The column collation (MySQL/PostgreSQL/Sqlite/SQLServer but not compatible) |
| [->comment( 'comment' )](#columncomment) | Add a comment to a column (MySQL/PostgreSQL/Oracle/SQLServer) |
| [->default( 1 )](#columndefault) | Default value of the column if no value was specified (default: `NULL`) |
| [->fixed( true )](#columnfixed) | If string or binary columns should have a fixed length |
| [->index( 'idx_col' )](#columnindex) | Add an index to the column, index name is optional |
| [->length( 32 )](#columnlength) | The maximum length of string and binary columns |
| [->null( true )](#columnnull) | Allow NULL values to be inserted into the column |
| [->precision( 12 )](#columnlength) | The maximum number of digits stored in DECIMAL and FLOAT columns incl. decimal digits |
| [->primary( 'pk_col' )](#columnprimary) | Add a primary key to the column, primary key name is optional |
| [->scale( 2 )](#columnscale) | The exact number of decimal digits used in DECIMAL and FLOAT columns |
| [->seq( true )](#columnseq) | Set INTEGER columns as auto-incrementing if no value was specified |
| [->spatial( 'spt_col' )](#columnspatial) | Add a spatial (geo) index to the column, index name is optional |
| [->unique( 'unq_col' )](#columnunique) | Add an unique index to the column, index name is optional |
| [->unsigned( true )](#columnunsigned) | Allow unsigned INTEGER values only (MySQL) |

To set custom schema options for columns, use the [`opt()`](#columncol) method, e.g.:

```php
$this->db()->table( 'test', function( $table ) {
	$table->string( 'code' )->opt( 'collation', 'utf8mb4' );
} );
```

It's even possible to set column modifiers for a specific database implementation
by passing the database type as third parameter:

```php
$this->db()->table( 'test', function( $table ) {
	$table->string( 'code' )->opt( 'collation', 'utf8mb4', 'mysql' );
} );
```

### Checking column existence

To check if a column already exists, use the [`hasColumn()`](#dbhascolumn) method:

```php
if( $this->db()->hasColumn ( 'users', 'name' ) ) {
    // The "name" column in the "users" table exists
}
```

You can check for several columns at once too. In that case, the [`hasColumn()`(#dbhascolumn)
method will only return `TRUE` if all columns exist:

```php
if( $this->db()->hasColumn ( 'users', ['name', 'status'] ) ) {
    // The "name" and "status" columns in the "users" table exists
}
```

If you already have a table object, you can use [`hasColumn()`(#tablehascolumn) too:

```php
if( $table->hasColumn ( 'name' ) ) {
    // The "name" column in the table exists
}

if( $table->hasColumn ( 'users', ['name', 'status'] ) ) {
    // The "name" and "status" columns in the table exists
}
```

Besides columns, you can also check if column modifiers are set and which value they have:

```php
if( $table->string( 'code' )->null() ) {
	// The "code" columns is nullable
}
```

It's possible to check for all column modifiers using these methods:

| Column modifier | Description |
|-----------------|-------------|
| [->autoincrement()](#columnautoincrement) | TRUE if the the column is auto-incrementing (alias for [`seq()`](#columnseq)) |
| [->charset()](#columncharset) | Used character set (MySQL) |
| [->collation()](#columncollation) | Used collation (MySQL/PostgreSQL/Sqlite/SQLServer but not compatible) |
| [->comment()](#columncomment) | Comment associated to the column (MySQL/PostgreSQL/Oracle/SQLServer) |
| [->default()](#columndefault) | Default value of the column |
| [->fixed()](#columnfixed) | If the string or binary column has a fixed length |
| [->length()](#columnlength) | The maximum length of the string or binary column |
| [->null()](#columnnull) | If NULL values are allowed |
| [->precision()](#columnlength) | The maximum number of digits stored in DECIMAL and FLOAT columns incl. decimal digits |
| [->scale()](#columnscale) | The exact number of decimal digits used in DECIMAL and FLOAT columns |
| [->seq()](#columnseq) | TRUE if the column is auto-incrementing |
| [->unsigned()](#columnunsigned) | If only unsigned INTEGER values are allowed (MySQL) |

To check for non-standard column modifiers, use the [`opt()`](#columnopt) method
without second parameter. Then, it will return the current value of the column modifier:

```php
if( $table->string( 'code' )->opt( 'charset' ) === 'utf8' ) {
	// The "code" columns uses UTF-8 charset (MySQL only)
}
```

### Changing columns

It's possible to change most column modifiers like the length of a string column:

```php
$this->db()->table( 'test', function( $table ) {
	$table->string( 'code' )->length( 64 );
} );
```

Some methods also offer additional parameters to set most often used modifiers
directly:

```php
$this->db()->table( 'test', function( $table ) {
	$table->string( 'code', 64 );
} );
```

If you need to change the column modifiers immediately because you want to migrate
the rows afterwards, use the [`up()`](#columnup) method to persist the changes:

```php
$this->db()->table( 'test', function( $table ) {
	$table->string( 'code', 64 )->null( true )->up();
	// modify rows from "test" table
} );
```

Changing the column type is possible by using the new method for the appropriate
type or the [`type()](#columntype) method:

```php
$this->db()->table( 'test', function( $table ) {
	$table->text( 'code' );
} );

// or

$this->db()->table( 'test', function( $table ) {
	$table->col( 'code', 'text' );
} );
```

Be aware that not all column types can be changed into another type or at
least not without data loss. You can change an INTEGER column to a BIGINT column
without problem but the other way round will fail. The same happens if you want
to change a VARCHAR column (string) into an INTEGER column.

### Dropping columns

To drop columns , use the [`dropColumn()`](#dbdropcolumn) method:

```php
$this->db()->dropColumn( 'users', 'name' );
```

You can drop several columns at once if you pass the name of all columns you want
to drop as array:

```php
$this->db()->dropColumn( 'users', ['name', 'status'] );
```

If you already have a table object, you can use [`dropColumn()`](#tabledropcolumn) too:

```php
$table->dropColumn( 'name' );
$table->dropColumn( ['name', 'status'] );
```

### Column methods

<nav>
<div class="method-header"><a href="#columns">Columns</a></div>
<ul class="method-list">
	<li><a href="#column__call">__call()</a></li>
	<li><a href="#column__get">__get()</a></li>
	<li><a href="#column__set">__set()</a></li>
	<li><a href="#columnautoincrement">autoincrement()</a></li>
	<li><a href="#columncomment">comment()</a></li>
	<li><a href="#columndefault">default()</a></li>
	<li><a href="#columnfixed">fixed()</a></li>
	<li><a href="#columnindex">index()</a></li>
	<li><a href="#columnlength">length()</a></li>
	<li><a href="#columnname">name()</a></li>
	<li><a href="#columnnull">null()</a></li>
	<li><a href="#columnopt">opt()</a></li>
	<li><a href="#columnprecision">precision()</a></li>
	<li><a href="#columnprimary">primary()</a></li>
	<li><a href="#columnscale">scale()</a></li>
	<li><a href="#columnseq">seq()</a></li>
	<li><a href="#columnspatial">spatial()</a></li>
	<li><a href="#columntype">type()</a></li>
	<li><a href="#columnunique">unique()</a></li>
	<li><a href="#columnunsigned">unsigned()</a></li>
	<li><a href="#columnup">up()</a></li>
</ul>
</nav>


#### Column::__call()

Calls custom methods or passes unknown method calls to the Doctrine column object

```php
public function __call( string $method, array $args )
```

* @param string `$method` Name of the method
* @param array `$args` Method parameters
* @return mixed Return value of the called method

**Examples:**

You can register custom methods that have access to the class properties of the Upscheme Column object:

```php
\Aimeos\Upscheme\Schema\Column::macro( 'platform', function( array $options ) {
	return $this->to->setPlatformOptions( $options );
} );

$column->platform( ['option' => 'value'] );
```

Available class properties are:

`$this->db`
: Upscheme DB object

`$this->table`
: Doctrine table schema

`$this->column`
: Doctrine column schema

Furthermore, you can call any [Doctrine column](https://github.com/doctrine/dbal/blob/3.1.x/src/Schema/Column.php) method directly, e.g.:

```php
$column->setPlatformOptions( ['option' => 'value'] );
```

#### Column::__get()

Returns the value for the given column option

```php
public function __get( string $name )
```

* @param string `$name` Column option name
* @return mixed Column option value

The list of available column options are:

* charset (MySQL)
* collation (MySQL, PostgreSQL, Sqlite and SQL Server)
* check
* unique (All)

**Examples:**

```php
$charset = $column->charset;

// same as
$charset = $column->opt( 'charset' );
```


#### Column::__set()

Sets the new value for the given column option

```php
public function __set( string $name, $value )
```

* @param string `$name` Column option name
* @param mixed Column option value

The list of available column options are:

* charset (MySQL)
* collation (MySQL, PostgreSQL, Sqlite and SQL Server)
* check
* unique (All)

**Examples:**

```php
$column->charset = 'utf8';

// same as
$column->opt( 'charset', 'utf8' );
```


#### Column::autoincrement()

Sets the column as autoincrement or returns the current value

```php
public function autoincrement( bool $value = null )
```

* @param bool&#124;null $value New autoincrement flag or NULL to return current value
* @return self&#124;bool Same object for setting the value, current value without parameter

This method is an alias for the [`seq()` method](#columnseq).

**Examples:**

```php
$value = $column->autoincrement();
$column->autoincrement( true );
```


#### Column::comment()

Sets the column comment or returns the current value

```php
public function comment( string $value = null )
```

* @param string&#124;null $value New column comment or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$comment = $column->comment();
$column->comment( 'column comment' );
```


#### Column::default()

Sets the column default value or returns the current value

```php
public function default( $value = null )
```

* @param mixed $value New column default value or NULL to return current value
* @return self&#124;mixed Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->default();
$column->default( 0 );
```


#### Column::fixed()

Sets the column fixed flag or returns the current value

```php
public function fixed( bool $value = null )
```

* @param string&#124;null $value New column fixed flag or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->fixed();
$column->fixed( true );
```


#### Column::index()

Creates a regular index for the column

```php
public function index( string $name = null ) : self
```

* @param string&#124;null Name of the index or NULL to generate automatically
* @return self Same object for fluid method calls

**Examples:**

```php
$column->index();
$column->index( 'idx_col' );
```


#### Column::length()

Sets the column length or returns the current value

```php
public function length( int $value = null )
```

* @param string&#124;null $value New column length or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->length();
$column->length( 32 );
```


#### Column::name()

Returns the name of the column

```php
public function name() : string
```

* @return string Column name

**Examples:**

```php
$name = $column->name();
```


#### Column::null()

Sets the column null flag or returns the current value

```php
public function null( bool $value = null )
```

* @param string&#124;null $value New column null flag or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->null();
$column->null( true );
```


#### Column::opt()

Sets the column option value or returns the current value

```php
public function opt( string $option, $value = null, $for = null )
```

* @param string $option Column option name
* @param mixed $value New column option value or NULL to return current value
* @param array&#124;string&#124;null $for Database type this option should be used for ("mysql", "postgresql", "sqlite", "mssql", "oracle", "db2")
* @return self&#124;mixed Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->opt( 'length' );
$column->opt( 'length', 64 );
```


#### Column::precision()

Sets the column precision or returns the current value

```php
public function precision( int $value = null )
```

* @param string&#124;null $value New column precision value or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->precision();
$column->precision( 10 );
```


#### Column::primary()

Creates a primary index for the column

```php
public function primary( string $name = null ) : self
```

* @param string&#124;null Name of the index or NULL to generate automatically
* @return self Same object for fluid method calls

**Examples:**

```php
$column->primary();
$column->primary( 'pk_col' );
```


#### Column::scale()

Sets the column scale or returns the current value

```php
public function scale( int $value = null )
```

* @param string&#124;null $value New column scale value or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->scale();
$column->scale( 3 );
```


#### Column::seq()

Sets the column as autoincrement or returns the current value

```php
public function seq( bool $value = null )
```

* @param bool&#124;null $value New autoincrement flag or NULL to return current value
* @return self&#124;bool Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->seq();
$column->seq( true );
```


#### Column::spatial()

Creates a spatial index for the column

```php
public function spatial( string $name = null ) : self
```

* @param string&#124;null Name of the index or NULL to generate automatically
* @return self Same object for fluid method calls

**Examples:**

```php
$column->spatial();
$column->spatial( 'idx_col' );
```


#### Column::type()

Sets the column type or returns the current value

```php
public function type( string $value = null )
```

* @param string&#124;null $value New column type or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->type();
$column->type( 'tinyint' );
```


#### Column::unique()

Creates an unique index for the column

```php
public function unique( string $name = null ) : self
```

* @param string&#124;null Name of the index or NULL to generate automatically
* @return self Same object for fluid method calls

**Examples:**

```php
$column->unique();
$column->unique( 'unq_col' );
```


#### Column::unsigned()

Sets the column unsigned flag or returns the current value

```php
public function unsigned( bool $value = null )
```

* @param bool&#124;null $value New column unsigned flag or NULL to return current value
* @return self&#124;bool Same object for setting the value, current value without parameter

**Examples:**

```php
$value = $column->unsigned();
$column->unsigned( true );
```


#### Column::up()

Applies the changes to the database schema

```php
public function up() : self
```

* @return self Same object for fluid method calls

**Examples:**

```php
$column->up();
```



## Foreign keys

The foreign key scheme object you get by calling `$table->foreign( '<col>', '<table>' )` in your migration task gives you access to the foreign key properties, e.g.:

```php
$this->db()->table( 'testref', function( $table ) {
	$table->id();
	$table->foreign( 'parentid', 'test' )->onDelete( 'SET NULL' );
} );
```


### Foreign Key methods

<nav>
<div class="method-header"><a href="#foreign-keys">Foreign keys</a></div>
<ul class="method-list">
	<li><a href="#foreign__call">__call()</a></li>
	<li><a href="#foreign__get">__get()</a></li>
	<li><a href="#foreign__set">__set()</a></li>
	<li><a href="#foreignname">name()</a></li>
	<li><a href="#foreignondelete">onDelete()</a></li>
	<li><a href="#foreignonupdate">onUpdate()</a></li>
	<li><a href="#foreignup">up()</a></li>
</ul>
</nav>


#### Foreign::__call()

Calls custom methods

```php
public function __call( string $method, array $args )
```

* @param string `$method` Name of the method
* @param array `$args` Method parameters
* @return mixed Return value of the called method

**Examples:**

You can register custom methods that have access to the class properties of the Upscheme Foreign object:

```php
\Aimeos\Upscheme\Schema\Foreign::macro( 'default', function() {
	$this->opts = ['onDelete' => 'SET NULL', 'onUpdate' => 'SET NULL'];
} );

$foreign->default();
```

Available class properties are:

`$this->dbaltable`
: Doctrine table schema

`$this->table`
: Upscheme Table object

`$this->localcol`
: Local column name or names

`$this->fktable`
: Foreign table name

`$this->fkcol`
: Foreign column name or names

`$this->name`
: Foreign key name

`$this->opts`
: Associative list of foreign key options (mainly "onDelete" and "onUpdate")


#### Foreign::__get()

Returns the value for the given foreign key option

```php
public function __get( string $name )
```

* @param string `$name` Foreign key option name
* @return mixed Foreign key option value

The list of available foreign key options are:

* onDelete
* onUpdate

Possible values for both options are:

* CASCADE : Update referenced value
* NO ACTION : No change in referenced value
* RESTRICT : Forbid changing values
* SET DEFAULT : Set referenced value to the default value
* SET NULL : Set referenced value to NULL

**Examples:**

```php
$value = $foreign->onDelete;
// same as
$value = $foreign->opt( 'onDelete' );
```


#### Foreign::__set()

Sets the new value for the given Foreign key option

```php
public function __set( string $name, $value )
```

* @param string `$name` Foreign key option name
* @param mixed Foreign key option value

The list of available Foreign key options are:

* onDelete
* onUpdate

Possible values for both options are:

* CASCADE : Update referenced value
* NO ACTION : No change in referenced value
* RESTRICT : Forbid changing values
* SET DEFAULT : Set referenced value to the default value
* SET NULL : Set referenced value to NULL

**Examples:**

```php
$foreign->onDelete = 'SET NULL';
// same as
$foreign->onDelete( 'SET NULL' );
$foreign->opt( 'onDelete', 'SET NULL' );
```


#### Foreign::name()

* Sets the name of the constraint or returns the current name

```php
public function name( string $value = null )
```

* @param string&#124;null $value New name of the constraint or NULL to return current value
* @return self&#124;string Same object for setting the name, current name without parameter

**Examples:**

```php
$fkname = $foreign->name();
```


#### Foreign::onDelete()

* Sets the action if the referenced row is deleted or returns the current value

```php
public function onDelete( string $value = null )
```

* @param string&#124;null $value Performed action or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

* Available actions are:
* - CASCADE : Delete referenced value
* - NO ACTION : No change in referenced value
* - RESTRICT : Forbid changing values
* - SET DEFAULT : Set referenced value to the default value
* - SET NULL : Set referenced value to NULL

**Examples:**

```php
$value = $foreign->onDelete();

$foreign->onDelete( 'SET NULL' );
// same as
$foreign->onDelete = 'SET NULL';
$foreign->opt( 'onDelete', 'SET NULL' );

$foreign->onDelete( 'SET NULL' )->onUpdate( 'SET NULL' );
```


#### Foreign::onUpdate()

* Sets the action if the referenced row is updated or returns the current value

```php
public function onUpdate( string $value = null )
```

* @param string&#124;null $value Performed action or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

* Available actions are:
* - CASCADE : Update referenced value
* - NO ACTION : No change in referenced value
* - RESTRICT : Forbid changing values
* - SET DEFAULT : Set referenced value to the default value
* - SET NULL : Set referenced value to NULL

**Examples:**

```php
$value = $foreign->onUpdate();

$foreign->onUpdate( 'SET NULL' );
// same as
$foreign->onUpdate = 'SET NULL';
$foreign->opt( 'onUpdate', 'SET NULL' );

$foreign->onUpdate( 'SET NULL' )->onDelete( 'SET NULL' );
```


#### Foreign::up()

* Applies the changes to the database schema

```php
public function up() : self
```

* @return self Same object for fluid method calls

**Examples:**

```php
$foreign->up();
```



## Sequences

The Sequence scheme object you get by calling `$db->sequence( '<name>' )` in your migration task gives you access to the sequence properties, e.g.:

```php
$this->db()->sequence( 'seq_test' )->start( 1000 )->step( 2 );

// same as
$this->db()->sequence( 'seq_test', function( $seq ) {
	$seq->start( 1000 );
	$seq->step( 2 );
} );
```


### Sequence methods

<nav>
<div class="method-header"><a href="#sequences">Sequences</a></div>
<ul class="method-list">
	<li><a href="#sequences__call">__call()</a></li>
	<li><a href="#sequences__get">__get()</a></li>
	<li><a href="#sequences__set">__set()</a></li>
	<li><a href="#sequencescache">cache()</a></li>
	<li><a href="#sequencesname">name()</a></li>
	<li><a href="#sequencesstart">start()</a></li>
	<li><a href="#sequencestep">step()</a></li>
	<li><a href="#sequencesup">up()</a></li>
</ul>
</nav>


#### Sequence::__call()

Calls custom methods or passes unknown method calls to the Doctrine table object

```php
public function __call( string $method, array $args )
```

* @param string `$method` Name of the method
* @param array `$args` Method parameters
* @return mixed Return value of the called method

**Examples:**

You can register custom methods that have access to the class properties of the Upscheme Sequence object:

```php
\Aimeos\Upscheme\Schema\Sequence::macro( 'default', function() {
	$this->start( 1 )->step( 2 );
} );

$sequence->default();
```

Available class properties are:

`$this->db`
: Upscheme DB object

`$this->sequence`
: Doctrine sequence schema


#### Sequence::__get()

Returns the value for the given sequence option

```php
public function __get( string $name )
```

* @param string `$name` Sequence option name
* @return mixed Sequence option value

**Examples:**

```php
$value = $sequence->getInitialValue();
// same as
$value = $sequence->start();
```


#### Sequence::__set()

Sets the new value for the given sequence option

```php
public function __set( string $name, $value )
```

* @param string `$name` Sequence option name
* @param mixed Sequence option value

**Examples:**

```php
$value = $sequence->setInitialValue( 1000 );
// same as
$value = $sequence->start( 1000 );
```


#### Sequence::cache()

Sets the cached size of the sequence or returns the current value

```php
public function cache( int $value = null )
```

* @param int $value New number of sequence IDs cached by the client or NULL to return current value
* @return self&#124;int Same object for setting value, current value without parameter

**Examples:**

```php
$value = $sequence->cache();
$sequence->cache( 100 );
```


#### Sequence::name()

Returns the name of the sequence

```php
public function name()
```

* @return string Sequence name

```php
$name = $sequence->name();
```


#### Sequence::start()

Sets the new start value of the sequence or returns the current value

```php
public function start( int $value = null )
```

* @param int $value New start value of the sequence or NULL to return current value
* @return self&#124;int Same object for setting value, current value without parameter

```php
$value = $sequence->start();
$sequence->start( 1000 );
```


#### Sequence::step()

Sets the step size of new sequence values or returns the current value

```php
public function step( string $value = null )
```

* @param int $value New step size the sequence is incremented or decremented by or NULL to return current value
* @return self&#124;int Same object for setting value, current value without parameter

```php
$value = $sequence->step();
$sequence->step( 2 );
```


#### Sequence::up()

Applies the changes to the database schema

```php
public function up() : self
```

* @return self Same object for fluid method calls

```php
$sequence->up();
```
