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
  * [Renaming objects](#renaming-objects)
  * [Removing objects](#removing-objects)
  * [Query/modify table rows](#Query-modify-table-rows)
  * [Executing custom SQL](#executing-custom-sql)
  * [Database methods](#database-methods)
* [Tables](#tables)
  * [Creating tables](#creating-tables)
  * [Setting table options](#setting-table-options)
  * [Checking table existence](#checking-table-existence)
  * [Changing tables](#changing-tables)
  * [Renaming tables](#renaming-tables)
  * [Dropping tables](#dropping-tables)
  * [Table methods](#table-methods)
* [Columns](#columns)
  * [Adding columns](#adding-columns)
  * [Available column types](#available-column-types)
  * [Column modifiers](#column-modifiers)
  * [Checking column existence](#checking-column-existence)
  * [Changing columns](#changing-columns)
  * [Renaming columns](#renaming-columns)
  * [Dropping columns](#dropping-columns)
  * [Column methods](#column-methods)
* [Foreign keys](#foreign-keys)
  * [Creating foreign keys](#creating-foreign-keys)
  * [Checking foreign key existence](#checking-foreign-key-existence)
  * [Dropping foreign keys](#dropping-foreign-keys)
  * [Foreign key methods](#foreign-key-methods)
* [Sequences](#sequences)
  * [Adding sequences](#adding-sequences)
  * [Checking sequence existence](#checking-sequence-existence)
  * [Dropping sequences](#dropping-sequences)
  * [Sequence methods](#sequence-methods)
* [Indexes](#indexes)
  * [Adding indexes](#adding-indexes)
  * [Checking index existence](#checking-index-existence)
  * [Renaming indexes](#renaming-indexes)
  * [Dropping indexes](#dropping-indexes)
  * [Custom index naming](#custom-index-naming)
* [Customizing Upscheme](#customizing-upscheme)
  * [Adding custom methods](#adding-custom-methods)
  * [Implementing custom columns](#implementing-custom-columns)



## Why Upscheme

Migrations are like version control for your database. They allow you to record
all changes and share them with others so they get the exact same state in their
installation.

For upgrading relational database schemas, two packages are currently used most
often: Doctrine DBAL and Doctrine migrations. While Doctrine DBAL does a good job
in abstracting the differences of several database implementations, it's API
requires writing a lot of code. Doctrine migrations on the other site has some
drawbacks which make it hard to use in all applications that support 3rd party
extensions.

### Doctrine DBAL drawbacks

The API of DBAL is very verbose and you need to write lots of code even for simple
things. Upscheme uses Doctrine DBAL to offer an easy to use API for upgrading the
database schema of your application with minimal code. Let's compare some example
code you have to write for DBAL and for Upscheme in a migration.

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

Doctrine Migration relies on migration classes that are named by the time they
have been created to ensure a certain order. Furthermore, it stores which migrations
has been executed in a table of your database. There are two major problems that
arise from that.

If your application supports 3rd party extensions, these extensions are likely to
add columns to existing tables and migrate data themselves. As there's no way to
define dependencies between migrations, it can get almost impossible to run
migrations in an application with several 3rd party extensions without conflicts.
To avoid that, Upscheme offers easy to use `before()` and `after()` methods in
each migration task where the tasks can define its dependencies to other tasks.

Because Doctrine Migrations uses a database table to record which migration
already has been executed, these records can get easily out of sync in case of
problems. Contrary, Upscheme only relies on the actual schema so it's possible
to upgrade from any state, regardless of what has happend before.

Doctrine Migrations also supports the reverse operations in `down()` methods so
you can roll back migrations which Upscheme does not. Experience has shown that
it's often impossible to roll back migrations, e.g. after adding a new colum,
migrating the data of an existing column and dropping the old column afterwards.
If the migration of the data was lossy, you can't recreate the same state in a
`down()` method. The same is the case if you've dropped a table. Thus, Upscheme
only offers scheme upgrading but no downgrading to avoid implicit data loss.


## Integrating Upscheme

After you've installed the `aimeos/upscheme` package using composer, you can use
the `Up` class to execute your migration tasks:

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

The `Up::use()` method requires two parameters: The database configuration and
the path(s) to the migration tasks. For the config, the array keys and the values
for *driver* must be supported by Doctrine DBAL. Available drivers are:

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

If you didn't use Doctrine DBAL before, your database configuration may have a
different structure and/or use different values for the database type. Upscheme
allows you to register a custom method that transforms your configration into
valid DBAL settings, e.g.:

```php
\Aimeos\Upscheme\Up::macro( 'connect', function( array $cfg ) {

	return \Doctrine\DBAL\DriverManager::getConnection( [
		'driver' => $cfg['adapter'],
		'host' => $cfg['host'],
		'dbname' => $cfg['database'],
		'user' => $cfg['username'],
		'password' => $cfg['password']
	] );
} );
```

Upscheme also supports several database connections which you can distinguish
by their key name:

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

A migration task only requires implementing the `up()` method and must be stored
in one of the directories passed to the `Up` class:

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

The file your class is stored in must have the same name (case sensitive) as
the class itself and the `.php` suffix, e.g:

```
class TestTable -> TestTable.php
```

There's no strict convention how to name migration task classes. You can either
name them by what they do (e.g. "CreateTestTable"), what they operate on (e.g.
"TestTable") or even use a timestamp (e.g. "20201231_Test"). If the tasks doesn't
contain dependencies, they are sorted and executed in in alphabethical order and
the sorting would be:

```
20201231_Test
CreateTestTable
TestTable
```

In your PHP file, always include the `namespace` statement first. The `use`
statement is optional and only needed as shortcut for the type hint for the
closure function argument. Your class also has to extend from the "Base" task
class or implement the ["Iface" task interface](https://github.com/aimeos/upscheme/blob/master/src/Task/Iface.php).

### Dependencies

To specify dependencies to other migration tasks, use the `after()` and `before()`
methods. Your task is executed after the tasks returned by `after()` and before
the tasks returned by `before()`:

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

The second parameter is the verbosity level and none or `v` are standard messages,
`vv` are messages that are only displayed if more verbosity is wanted while `vvv` is
for debugging messages. There's also a third parameter for indenting the messages:

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

In the `up()` method, you have access to the database schema using the `db()`
method. In case you've passed more than one database configuration to `Up::use()`,
you can access the different schemas by their configuration key:

```php
// $config = ['db' => [...], 'temp' => [...]];
// \Aimeos\Upscheme\Up::use( $config, '...' )->up();

$this->db();
$this->db( 'db' );
$this->db( 'temp' );
```

If you pass no config key or one that doesn't exist, the first configuration is
returned ("db" in this case). By using the available methods of the database schema
object, you can add, update or drop tables, columns, indexes and other database
objects. Also, you can use [`insert()`](#dbinsert), [`select()`](#dbselect),
[`update()`](#dbupdate), [`delete()`](#dbdelete) and [`stmt()`](#dbstmt) to
manipulate the records of the tables.

After each migration task, the schema updates made in the task are automatically
applied to the database. If you need to persist a change immediately because you
want to insert data, call `$this->db()->up()` yourself. The `up()` method is also
available in any table, sequence, and column object so you can call `up()`
everywhere.

In cases you need two different database connections because you want to execute
SELECT and INSERT/UPDATE/DELETE statements at the same time, pass TRUE as second
parameter to `db()` to get the database schema including a new connection:

```php
$db1 = $this->db();
$db2 = $this->db( 'db', true );

foreach( $db1->select( 'users', ['status' => false] ) as $row ) {
	$db2->insert( 'oldusers', $row );
}

$db2->delete( 'users', ['status' => false] );
```

All schema changes made are applied to the database before the schema with the
new connection is returned. To avoid database connections to pile up until the
database server rejects new connections, always calll [`close()`](#dbclose) for
new connections created by `db( '<name>', true )`:

```php
$db2->close();
```


## Database

### Accessing objects

You get the database schema object in your task by calling `$this->db()` as
described in the [schema section](#schemas). It gives you full access to the
database schema including all tables, sequences and other schema objects:

```php
$table = $this->db()->table( 'users' );
$seq = $this->db()->sequence( 'seq_users' );
```

If the table or seqence doesn't exist, it will be created. Otherwise, the existing
table or sequence object is returned. In both cases, you can modify the objects
afterwards and add e.g. new columns to the table.

### Checking existence

You can test for tables, columns, indexes, foreign keys and sequences using the
database schema returned by `$this->db()`:

```php
$db = $this->db();

if( $db->hasTable( 'users' ) ) {
    // The "users" table exists
}

if( $db->hasColumn( 'users', 'name' ) ) {
    // The "name" column in the "users" table exists
}

if( $db->hasIndex( 'users', 'idx_name' ) ) {
    // The "idx_name" index in the "users" table exists
}

if( $db->hasForeign( 'users_address', 'fk_users_id' ) ) {
    // The foreign key "fk_users_id" in the "users_address" table exists
}

if( $db->hasSequence( 'seq_users' ) ) {
    // The "seq_users" sequence exists
}
```

### Renaming objects

The database object returned by `$this->db()` offers the possibility to rename
tables, columns and indexes using the [`renameTable()`](#dbrenametable),
[`renameColumn()`](#dbrenamecolumn) and [`renameIndex()`](#dbrenameindex):

```php
$db = $this->db();

// Renames the table "users" to "accounts"
$db->renameTable( 'users', 'account' );

// Renames the column "label" to "name" in the "users" table
$db->renameColumn( 'users', 'label', 'name' );

// Renames the column "idx_label" to "idx_name" in the "users" table
$db->renameIndex( 'users', 'idx_label', 'idx_name' );
```

### Removing objects

The database object returned by `$this->db()` also has methods for dropping tables,
columns, indexes, foreign keys and sequences:

```php
$db = $this->db();

// Drops the foreign key "fk_users_id" from the "users_address" table
$db->dropForeign( 'users_address', 'fk_users_id' );

// Drops the "idx_name" index from the "users" table
$db->dropIndex( 'users', 'idx_name' );

// Drops the "name" column from the "users" table
$db->dropColumn( 'users', 'name' );

// Drops the "seq_users" sequence
$db->dropSequence( 'seq_users' );

// Drops the "users" table
$db->dropTable( 'users' );
```

If the table, column, index, foreign key or sequence doesn't exist, it is silently
ignored. For cases where you need to know if they exist, use the
[`hasTable()`](#dbhastable), [`hasColumn()`](#dbhascolumn), [`hasIndex()`](#dbhasindex),
[`hasForeign()`](#dbhasforeign) and [`hasSeqence()`](#dbhassequence) methods before
like described in the ["Checking for existence"](#checking-for-existence) section.

### Query and modify table rows

The [`insert()`](#dbinsert), [`select()`](#dbselect), [`update()`](#dbupdate) and
[`delete()`](#dbdelete) methods are an easy way to add, retrieve, modify and
remove rows in any table:

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

If you use [`select()`](#dbselect) simultaniously with [`insert()`](#dbinsert),
[`update()`](#dbupdate) or [`delete()`](#dbdelete), you must create a second
database connection because the [`select()`](#dbselect) statement will return
rows while you send new commands to the database server. This only works on
separate connections, not on the same.

You can only pass simple key/value pairs for conditions to the methods which are
combined by AND. If you need more complex queries, use the [`stmt()`](#dbstmt)
instead:

```php
$db = $this->db();

$result = $db->stmt()->select( 'id', 'name' )
	->from( 'users' )
	->where( 'status != ?' )
	->setParameter( 0, false )
	->execute();

$db->stmt()->delete( 'users' )
	->where( 'status != ?' )
	->setParameter( 0, false )
	->execute();

$db->stmt()->update( 'users' )
	->set( 'status', '?' )
	->where( 'status != ?' )
	->setParameters( [true, false] )
	->execute();
```

The [`stmt()`](#dbstmt) method returns a `Doctrine\DBAL\Query\QueryBuilder` object
which enables you to build more advanced statement. Please have a look into the
[Doctrine Query Builder](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html)
documentation for more details.

If you want to use values directly in a SQL statement (use prepared statements for
security reasons whenever possible!), you have to quote the values using the
[`q()](#dbq) method:

```php
$db = $this->db();

$result = $db->stmt()->select( '*' )->from( 'products' )
	->where( 'status = ' . $db->q( $_GET['status'] ) )->execute();
```

Similarly, if your schema contains reserved keywords, e.g. as column names, you
have to quote them as well using the [`qi()`](#dbqi) method:

```php
$db = $this->db();

$result = $db->stmt()->select( $db->qi( 'key' ) )->from( 'products' )->execute();
```

### Executing custom SQL

Doctrine only supports a common subset of SQL statements and not all possibilities
the database vendors have implemented. To remove that limit, Upscheme offers the
[`exec()`](#dbexec), [`for()`](#dbfor) and [`query()`](#dbquery) methods to execute
custom SQL statements not supported by Doctrine DBAL.

To execute custom SQL queries use the [`query()`](#dbquery) method which returns a
result set you can iterate over:

```php
$sql = 'SELECT id, label, status FROM product WHERE label LIKE ?';
$result = $this->db()->query( $sql, ['test%'] );

foreach( $result->iterateKeyValue() as $key => $row ) {
	// ...
}
```

For all other SQL statements use the [`exec()`](#dbexec) method wich returns the
number of affected rows:

```php
$sql = 'UPDATE product SET status=? WHERE status=?';
$num = $this->db()->exec( $sql, [1, 0] );
```

Using the [`for()`](#dbfor) method, you can also execute statements depending on
the database platform:

```php
$this->db()->for( 'mysql', 'CREATE FULLTEXT INDEX idx_text ON product (text)' );
```

Specifying the database platform is very useful for creating special types
of indexes where the syntax differs between the database implementations.

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
	<li><a href="#dbexec">exec()</a></li>
	<li><a href="#dbfor">for()</a></li>
	<li><a href="#dbhascolumn">hasColumn()</a></li>
	<li><a href="#dbhasforeign">hasForeign()</a></li>
	<li><a href="#dbhasindex">hasIndex()</a></li>
	<li><a href="#dbhassequence">hasSequence()</a></li>
	<li><a href="#dbhastable">hasTable()</a></li>
	<li><a href="#dbinsert">insert()</a></li>
	<li><a href="#dblastid">lastId()</a></li>
	<li><a href="#dbname">name()</a></li>
	<li><a href="#dbq">q()</a></li>
	<li><a href="#dbqi">qi()</a></li>
	<li><a href="#dbquery">query()</a></li>
	<li><a href="#dbrenamecolumn">renameColumn()</a></li>
	<li><a href="#dbrenameindex">renameIndex()</a></li>
	<li><a href="#dbrenametable">renameTable()</a></li>
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

You can register custom methods that have access to the class properties of the
Upscheme DB object:

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

Furthermore, you can call any [Doctrine schema](https://github.com/doctrine/dbal/blob/3.1.x/src/Schema/Schema.php)
method directly, e.g.:

```php
$db->hasExplicitForeignKeyIndexes();
```


#### DB::close()

Closes the database connection

```php
public function close()
```

Call `close()` only for DB schema objects created with `$this->db( '...', true )`.
Otherwise, you will close the main connection and DBAL has to reconnect to the
server which will degrade performance!

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

Several conditions passed in the second parameter are combined by "AND". If you
need more complex statements, use the [`stmt()`](#dbstmt) method instead.


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

If the column or one of the columns doesn't exist, it will be silently ignored.


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

If the foreign key constraint or one of the constraints doesn't exist, it will be
silently ignored.


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

If the index or one of the indexes doesn't exist, it will be silently ignored.


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

If the sequence or one of the sequences doesn't exist, it will be silently ignored.


#### DB::dropTable()

Drops the table given by its name if it exists

```php
public function dropTable( $name ) : self
```

* @param array&#124;string `$name` Name of the table or tables
* @return self Same object for fluid method calls

**Examples:**

```php
$db->dropTable( 'test' );
$db->dropTable( ['test', 'test2'] );
```

If the table or one of the tables doesn't exist, it will be silently ignored.


#### DB::exec()

Executes a custom SQL statement

```php
public function exec( string $sql, array $params = [], array $types = [] ) : int
```

* @param string $sql Custom SQL statement
* @param array $params List of positional parameters or associative list of placeholders and parameters
* @param array $types List of DBAL data types for the positional or associative placeholder parameters
* @return int Number of affected rows

The database changes are not applied immediately so always call up()
before executing custom statements to make sure that the tables you want
to use has been created before!

**Examples:**

```php
$sql = 'UPDATE product SET status=? WHERE status=?';
$num = $this->db()->exec( $sql, [1, 0] );
```


#### DB::for()

Executes a custom SQL statement if the database is of the given type

```php
public function for( $type, $sql ) : self
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


#### DB::name()

Returns the name of the database

```php
public function name() : string
```

* @return string Database name

**Examples:**

```php
$db->name();
```


#### DB::q()

Quotes a value

```php
public function q( $value, $type = \Doctrine\DBAL\ParameterType::STRING ) : string
```

* @param mixed $value Value to use in a non-prepared SQL query
* @param mixed $type DBAL parameter type
* @return string Quoted value

**Examples:**

```php
$result = $db->stmt()->select( '*' )->from( 'products' )
	->where( 'status = ' . $db->q( $_GET['status'] ) )->execute();
```


#### DB::qi()

Quotes a database identifier

```php
public function qi( string $identifier ) : string
```

* @param string $identifier Identifier like table or column name
* @return string Quoted identifier

**Examples:**

```php
$result = $db->stmt()->select( $db->qi( 'key' ) )->from( 'products' )->execute();
```


#### DB::query()

Executes a custom SQL query

```php
public function query( string $sql, array $params = [], array $types = [] ) : \Doctrine\DBAL\Result
```

* @param string $sql Custom SQL statement
* @param array $params List of positional parameters or associative list of placeholders and parameters
* @param array $types List of DBAL data types for the positional or associative placeholder parameters
* @return \Doctrine\DBAL\Result DBAL result set object

**Examples:**

```php
$result = $db->query( 'SELECT id, label, status FROM product WHERE label LIKE ?', ['test%'] );

foreach( $result->iterateKeyValue() as $key => $row ) {
	// ...
}
```


#### DB::renameColumn()

Renames a column or a list of columns

```php
public function renameColumn( string $table, $from, string $to = null ) : self
```

* @param string `$table` Name of the table
* @param array&#124;string `$from` Column name or array of old/new column names
* @param string&#124;null `$to` New column name ignored if first parameter is an array
* @return self Same object for fluid method calls

**Examples:**

```php
// single column
$db->renameColumn( 'testtable', 'test_col', 'test_column' );

// rename several columns at once
$db->renameColumn( 'testtable', ['tcol' => 'testcol', 'tcol2' => 'testcol2'] );
```


#### DB::renameIndex()

Renames a column or a list of columns

```php
public function renameIndex( string $table, $from, string $to = null ) : self
```

* @param string `$table` Name of the table
* @param array&#124;string `$from` Index name or array of old/new index names
* @param string&#124;null `$to` New index name ignored if first parameter is an array
* @return self Same object for fluid method calls

**Examples:**

```php
// single index
$db->renameIndex( 'testtable', 'idxcol', 'idx_column' );

// rename several indexes at once
$db->renameIndex( 'testtable', ['idxcol' => 'idx_column', 'idxcol2' => 'idx_column2'] );
```


#### DB::renameTable()

Renames a table or a list of tables

```php
public function renameTable( $from, string $to = null ) : self
```

* @param array&#124;string `$from` Table name or array of old/new table names
* @param string&#124;null `$to` New table name ignored if first parameter is an array
* @return self Same object for fluid method calls

**Examples:**

```php
// single table
$db->renameTable( 'testtable', 'newtable' );

// rename several tables at once
$db->renameTable( ['testtable' => 'newtable', 'oldtable' => 'testtable2'] );
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

Several conditions passed in the second parameter are combined by "AND". If you
need more complex statements, use the [`stmt()`](#dbstmt) method instead.


#### DB::sequence()

Returns the sequence object for the given name

```php
public function sequence( string $name, \Closure $fcn = null ) : Sequence
```

* @param string `$name` Name of the sequence
* @param \Closure&#124;null `$fcn` Anonymous function with ($sequence) parameter creating or updating the sequence definition
* @return \Aimeos\Upscheme\Schema\Sequence Sequence object

If the sequence doesn't exist yet, it will be created. To persist the changes in the
database, you have to call `up()`.

**Examples:**

```php
$sequence = $db->sequence( 'seq_test' );

$sequence = $db->sequence( 'seq_test', function( $seq ) {
	$seq->start( 1000 )->step( 2 )->cache( 100 );
} )->up();
```


#### DB::stmt()

Returns the query builder for a new SQL statement

```php
public function stmt() : \Doctrine\DBAL\Query\QueryBuilder
```

* @return \Doctrine\DBAL\Query\QueryBuilder Query builder object

**Examples:**

```php
$db->stmt()->delete( 'test' )->where( 'status = ?' )->setParameter( 0, false )->execute();
$db->stmt()->update( 'test' )->set( 'status', '?' )->setParameter( 0, true )->execute();
$result = $db->stmt()->select( 'id', 'label' )->from( 'test' )->execute();
```

For more details about the available Doctrine QueryBuilder methods, please have
a look at the [Doctrine documentation](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html#building-a-query).


#### DB::table()

Returns the table object for the given name

```php
public function table( string $name, \Closure $fcn = null ) : Table
```

* @param string `$name` Name of the table
* @param \Closure&#124;null `$fcn` Anonymous function with ($table) parameter creating or updating the table definition
* @return \Aimeos\Upscheme\Schema\Table Table object

If the table doesn't exist yet, it will be created. To persist the changes in the
database, you have to call `up()`.

**Examples:**

```php
$table = $db->table( 'test' );

$table = $db->table( 'test', function( $t ) {
	$t->id();
	$t->string( 'label' );
	$t->bool( 'status' );
} )->up();
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

Several conditions passed in the second parameter are combined by "AND". If you
need more complex statements, use the [`stmt()`](#dbstmt) method instead.



## Tables

### Creating tables

The table scheme object you get by calling [`table()`](#dbtable) in your migration
task gives you full access to the table and you can add, change or remove columns,
indexes and foreign keys, e.g.:

```php
$this->db()->table( 'test', function( $table ) {
	$table->id();
	$table->string( 'label' );
	$table->col( 'status', 'tinyint' )->default( 0 );
} );
```

Besides the [`col()`](#tablecol) method which can add columns of arbitrary types,
there are some shortcut methods for types available in all database server implementations:

| Column type | Description |
|-------------|-------------|
| [bigid](#tablebigid) | BIGINT column with a sequence/autoincrement and a primary key assigned |
| [bigint](#tablebigint) | BIGINT column with a range from −9223372036854775808 to 9223372036854775807 |
| [binary](#tablebinary) | VARBINARY column with up to 255 bytes |
| [blob](#tableblob) | BLOB column with up to 2GB |
| [bool](#tablebool) | BOOLEAN/BIT/NUMBER colum, alias for "boolean" |
| [boolean](#tableboolean) | BOOLEAN/BIT/NUMBER colum for TRUE/FALSE resp. 0/1 values |
| [char](#tablechar) | CHAR column with a fixed number of characters |
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
	$table->temporary = true;
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

In case you need to know the current values of the table options:

```php
$this->db()->table( 'test', function( $table ) {
	// return the used table engine (only MySQL, MariaDB, etc.)
	$engine = $table->engine;

	// returns TRUE if it's a temporary table
	$isTemp = $table->temporary;

	// return the current charset
	$charset = $table->charset;

	// return the current collation
	$collation = $table->collation;
} );
```

### Checking table existence

To check if a table already exists, use the [`hasTable()`](#dbhastable) method:

```php
if( $this->db()->hasTable( 'users' ) ) {
    // The "users" table exists
}
```

You can check for several tables at once too:

```php
if( $this->db()->hasTable( ['users', 'addresses'] ) ) {
    // The "users" and "addresses" tables exist
}
```

The [`hasTable()`](#dbhastable) method will only return TRUE if all tables exist.

### Changing tables

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

### Renaming tables

The database object returned by `$this->db()` can rename tables when using the
[`renameTable()`](#dbrenametable) method:

```php
// Renames the table "users" to "accounts"
$this->db()->renameTable( 'users', 'account' );
```

It's also possible to rename several tables at once if you pass an associative
array which old and new names as key/value pairs:

```php
// Renames the table "users" to "accounts" and "blog" to "posts"
$this->db()->renameTable( ['users' => 'account', 'blog' => 'posts'] );
```

### Dropping tables

To remove a table, you should use the [`dropTable()`](#dbdroptable) method from
the database schema:

```php
$this->db()->dropTable( 'users' );
```

You can also drop several tables at once by passing the list as array:

```php
$this->db()->dropTable( ['users', 'addresses'] );
```

Tables are only removed if they exist. If a table doesn't exist any more, no error
is reported:

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
	<li><a href="#tablechar">char()</a></li>
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
	<li><a href="#tablerenamecolumn">renameColumn()</a></li>
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

You can register custom methods that have access to the class properties of the
Upscheme Table object:

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

Furthermore, you can call any [Doctrine table](https://github.com/doctrine/dbal/blob/3.1.x/src/Schema/Table.php)
method directly, e.g.:

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


#### Table::char()

Creates a new column of type "char" with a fixed type or returns the existing one

```php
public function char( string $name, int $length ) : Column
```

* @param string `$name` Name of the column
* @param int `$length` Length of the column in characters
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->char( 'testcol', 3 );
```


#### Table::col()

Creates a new column or returns the existing one

```php
public function col( string $name, string $type = null ) : Column
```

* @param string `$name` Name of the column
* @param string|null `$type` Type of the column
* @return \Aimeos\Upscheme\Schema\Column Column object

If the column doesn't exist yet, it will be created.

**Examples:**

```php
$table->col( 'testcol' );
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

If the column or one of the columns doesn't exist, it will be silently ignored.
The change won't be applied until the migration task finishes or `up()` is called.

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

If the index or one of the indexes doesn't exist, it will be silently ignored.
The change won't be applied until the migration task finishes or `up()` is called.

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

If the foreign key constraint or one of the constraints doesn't exist, it will be
silently ignored. The change won't be applied until the migration task finishes
or `up()` is called.

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

If the primary key doesn't exist, it will be silently ignored. The change won't
be applied until the migration task finishes or `up()` is called.

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

* @param array&#124;string `$localcolumn` Name of the local column or columns
* @param string `$foreigntable` Name of the referenced table
* @param array&#124;string `$localcolumn` Name of the referenced column or columns
* @param string&#124;null Name of the foreign key constraint and foreign key index or NULL for autogenerated name
* @return \Aimeos\Upscheme\Schema\Foreign Foreign key constraint object

The length of the foreign key name shouldn't be longer than 30 characters for
maximum compatibility.

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

* @param array&#124;string `$columns` Name of the columns or columns spawning the index
* @param string&#124;null `$name` Index name or NULL for autogenerated name
* @return self Same object for fluid method calls

The length of the index name shouldn't be longer than 30 characters for maximum
compatibility.

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

* @param string `$name` Name of the table-related custom schema option
* @param mixed `$value` Value of the custom schema option
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

* @param array&#124;string `$columns` Name of the columns or columns spawning the index
* @param string&#124;null `$name` Index name or NULL for autogenerated name
* @return self Same object for fluid method calls

The length of the index name shouldn't be longer than 30 characters for maximum
compatibility.

**Examples:**

```php
$table->primary( 'testcol' );
$table->primary( ['testcol', 'testcol2'] );
$table->primary( 'testcol', 'pk_test_testcol' );
```


#### Table::renameColumn()

Renames a column or a list of columns

```php
public function renameColumn( $from, string $to = null ) : self
```

* @param array&#124;string `$from` Column name or array of old/new column names
* @param string&#124;null `$to` New column name ignored if first parameter is an array
* @return self Same object for fluid method calls

**Examples:**

```php
// single column
$table->renameColumn( 'test_col', 'test_column' );

// rename several columns at once
$table->renameColumn( ['tcol' => 'testcol', 'tcol2' => 'testcol2'] );
```


#### Table::renameIndex()

Renames an index or a list of indexes

```php
public function renameIndex( $from, string $to = null ) : self
```

* @param array&#124;string `$from` Index name or array of old/new index names (if new index name is NULL, it will be generated)
* @param string&#124;null `$to` New index name or NULL for autogenerated name (ignored if first parameter is an array)
* @return self Same object for fluid method calls

The length of the indexes name shouldn't be longer than 30 characters for maximum
compatibility.

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

* @param array&#124;string `$columns` Name of the columns or columns spawning the index
* @param string&#124;null `$name` Index name or NULL for autogenerated name
* @return self Same object for fluid method calls

The length of the index name shouldn't be longer than 30 characters for maximum
compatibility.

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

This type should be used for up to 255 characters. For more characters, use the
"text" type. If the column doesn't exist yet, it will be created.

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

* @param array&#124;string `$columns` Name of the columns or columns spawning the index
* @param string&#124;null `$name` Index name or NULL for autogenerated name
* @return self Same object for fluid method calls

The length of the index name shouldn't be longer than 30 characters for maximum
compatibility.

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

The column schema object you get by calling [`col()`](#tablecol) in your migration
task gives you access to all column properties. There are also shortcuts available
for column types supported by all databases. Each column can be changed by one or
more modifier methods and you can also add indexes to single columns, e.g.:

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
| [bigid](#tablebigid) | BIGINT column with a sequence/autoincrement and a primary key assigned |
| [bigint](#tablebigint) | BIGINT column with a range from −9223372036854775808 to 9223372036854775807 |
| [binary](#tablebinary) | VARBINARY column with up to 255 bytes |
| [blob](#tableblob) | BLOB column with up to 2GB |
| [bool](#tablebool) | BOOLEAN/BIT/NUMBER colum, alias for "boolean" |
| [boolean](#tableboolean) | BOOLEAN/BIT/NUMBER colum for TRUE/FALSE resp. 0/1 values |
| [char](#tablechar) | CHAR column with a fixed number of characters |
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
| [autoincrement(true)](#columnautoincrement) | Set INTEGER columns as auto-incrementing (alias for [`seq()`](#columnseq)) |
| [charset('utf8')](#columncharset) | The character set used by the column (MySQL) |
| [collation('binary')](#columncollation) | The column collation (MySQL/PostgreSQL/Sqlite/SQLServer but not compatible) |
| [comment('comment')](#columncomment) | Add a comment to a column (MySQL/PostgreSQL/Oracle/SQLServer) |
| [default(1)](#columndefault) | Default value of the column if no value was specified (default: `NULL`) |
| [fixed(true)](#columnfixed) | If string or binary columns should have a fixed length |
| [index('idx_col')](#columnindex) | Add an index to the column, index name is optional |
| [length(32)](#columnlength) | The max. length of string and binary columns |
| [null(true)](#columnnull) | Allow NULL values to be inserted into the column |
| [precision(12)](#columnlength) | The max. number of digits stored in DECIMAL and FLOAT columns incl. decimal digits |
| [primary('pk_col')](#columnprimary) | Add a primary key to the column, primary key name is optional |
| [scale(2)](#columnscale) | The exact number of decimal digits used in DECIMAL and FLOAT columns |
| [seq(true)](#columnseq) | Set INTEGER columns as auto-incrementing if no value was specified |
| [spatial('idx_col')](#columnspatial) | Add a spatial (geo) index to the column, index name is optional |
| [unique('unq_col')](#columnunique) | Add an unique index to the column, index name is optional |
| [unsigned(true)](#columnunsigned) | Allow unsigned INTEGER values only (MySQL) |

To set custom schema options for columns, use the [`opt()`](#columnopt) method, e.g.:

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
if( $this->db()->hasColumn( 'users', 'name' ) ) {
    // The "name" column in the "users" table exists
}
```

You can check for several columns at once too. In that case, the [`hasColumn()`](#dbhascolumn)
method will only return TRUE if all columns exist:

```php
if( $this->db()->hasColumn( 'users', ['name', 'status'] ) ) {
    // The "name" and "status" columns in the "users" table exists
}
```

If you already have a table object, you can use [`hasColumn()`](#tablehascolumn)
as well:

```php
if( $table->hasColumn( 'name' ) ) {
    // The "name" column in the table exists
}

if( $table->hasColumn( ['name', 'status'] ) ) {
    // The "name" and "status" columns in the table exists
}
```

Besides columns, you can also check if column modifiers are set and which value
they have:

```php
if( $table->string( 'code' )->null() ) {
	// The "code" columns is nullable
}
```

Retrieving the current column modifier values is possible using these methods:

| Column modifier | Description |
|-----------------|-------------|
| [autoincrement()](#columnautoincrement) | TRUE if the the column is auto-incrementing (alias for [`seq()`](#columnseq)) |
| [charset()](#columncharset) | Used character set (MySQL) |
| [collation()](#columncollation) | Used collation (MySQL/PostgreSQL/Sqlite/SQLServer but not compatible) |
| [comment()](#columncomment) | Comment associated to the column (MySQL/PostgreSQL/Oracle/SQLServer) |
| [default()](#columndefault) | Default value of the column |
| [fixed()](#columnfixed) | TRUE if the string or binary column has a fixed length |
| [length()](#columnlength) | The maximum length of the string or binary column |
| [null()](#columnnull) | TRUE if NULL values are allowed |
| [precision()](#columnlength) | The maximum number of digits stored in DECIMAL and FLOAT columns incl. decimal digits |
| [scale()](#columnscale) | The exact number of decimal digits used in DECIMAL and FLOAT columns |
| [seq()](#columnseq) | TRUE if the column is auto-incrementing |
| [unsigned()](#columnunsigned) | TRUE if only unsigned INTEGER values are allowed (MySQL) |

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
type or the [`col()`](#tablecol) method:

```php
$this->db()->table( 'test', function( $table ) {
	$table->text( 'code' );
	// or
	$table->col( 'code', 'text' );
} );
```

Be aware that not all column types can be changed into another type or at
least not without data loss. You can change an INTEGER column to a BIGINT column
without problem but the other way round will fail. The same happens if you want
to change a VARCHAR column (string) into an INTEGER column.

### Renaming columns

If a table object is already available, you can use its [`renameColumn()`](#tablerenamecolumn)
method to rename one or more columns:

```php
$this->db()->table( 'testtable', function( $table ) {
	// single column
	$table->renameColumn( 'label', 'name' );

	// multiple columns
	$table->renameColumn( ['label' => 'name', 'stat' => 'status'] );
} );
```

It's also possible to rename columns directly, using the [`renameColumn()`](#dbrenamecolumn)
method of the DB schema:

```php
// single column
$this->db()->renameColumn( 'testtable', 'label', 'name' );

// multiple columns
$this->db()->renameColumn( 'testtable', ['label' => 'name', 'stat' => 'status'] );
```

### Dropping columns

To drop columns, use the [`dropColumn()`](#dbdropcolumn) method from the DB schema
object:

```php
$this->db()->dropColumn( 'users', 'name' );
```

You can drop several columns at once if you pass the name of all columns you want
to drop as array:

```php
$this->db()->dropColumn( 'users', ['name', 'status'] );
```

If you already have a table object, you can use [`dropColumn()`](#tabledropcolumn)
too:

```php
// single column
$table->dropColumn( 'name' );

// multiple columns
$table->dropColumn( ['name', 'status'] );
```

In all cases, columns are only removed if they exist. No error is reported if one
or more columns doesn't exist in the table.

### Column methods

<nav>
<div class="method-header"><a href="#columns">Columns</a></div>
<ul class="method-list">
	<li><a href="#column__call">__call()</a></li>
	<li><a href="#column__get">__get()</a></li>
	<li><a href="#column__set">__set()</a></li>
	<li><a href="#columnautoincrement">autoincrement()</a></li>
	<li><a href="#columncharset">charset()</a></li>
	<li><a href="#columncollation">collation()</a></li>
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

You can register custom methods that have access to the class properties of the
Upscheme Column object:

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

Furthermore, you can call any [Doctrine column](https://github.com/doctrine/dbal/blob/3.1.x/src/Schema/Column.php)
method directly, e.g.:

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

* @param bool&#124;null `$value` New autoincrement flag or NULL to return current value
* @return self&#124;bool Same object for setting the value, current value without parameter

This method is an alias for the [`seq()`](#columnseq) method.

**Examples:**

```php
$value = $column->autoincrement();
$column->autoincrement( true );
```


#### Column::charset()

Sets the column charset or returns the current value

```php
public function charset( string $value = null )
```

* @param string&#124;null `$value` New column charset or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$comment = $column->charset();
$column->charset( 'utf8' );
```


#### Column::collation()

Sets the column collation or returns the current value

```php
public function collation( string $value = null )
```

* @param string&#124;null `$value` New column collation or NULL to return current value
* @return self&#124;string Same object for setting the value, current value without parameter

**Examples:**

```php
$comment = $column->collation();
$column->collation( 'binary' );
```


#### Column::comment()

Sets the column comment or returns the current value

```php
public function comment( string $value = null )
```

* @param string&#124;null `$value` New column comment or NULL to return current value
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

* @param mixed `$value` New column default value or NULL to return current value
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

* @param string&#124;null `$value` New column fixed flag or NULL to return current value
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

* @param string&#124;null `$name` Name of the index or NULL to generate automatically
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

* @param string&#124;null `$value` New column length or NULL to return current value
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

* @param string&#124;null `$value` New column null flag or NULL to return current value
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

* @param string `$option` Column option name
* @param mixed `$value` New column option value or NULL to return current value
* @param array&#124;string&#124;null `$for` Database type this option should be used for ("mysql", "postgresql", "sqlite", "mssql", "oracle", "db2")
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

* @param string&#124;null `$value` New column precision value or NULL to return current value
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

* @param string&#124;null `$name` Name of the index or NULL to generate automatically
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

* @param string&#124;null `$value` New column scale value or NULL to return current value
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

* @param bool&#124;null `$value` New autoincrement flag or NULL to return current value
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

* @param string&#124;null `$name` Name of the index or NULL to generate automatically
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

* @param string&#124;null `$value` New column type or NULL to return current value
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

* @param string&#124;null `$name` Name of the index or NULL to generate automatically
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

* @param bool&#124;null `$value` New column unsigned flag or NULL to return current value
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

### Creating foreign keys

Upscheme offers support for foreign key constraints, which enforce the integrity
of data between two tables. For example, if the `parentid` column of the
`users_address` table references the `id` column of the `users` table, there can
be no rows in the `users_address` table without a matching row in the `users`
table. Calling the [`foreign()`](#tableforeign) method will create such a
constraint:

```php
$this->db()->table( 'users', function( $table ) {
	$table->id();
} );

$this->db()->table( 'users_address', function( $table ) {
	$table->foreign( 'parentid', 'users' );
} );
```

**Note:** The column (`parentid`) will and must have the same data type and column
modifiers as the referenced column (`id`). The [`foreign()`](#tableforeign) method
ensures that and will create a new index with the same name as the foreign key
constraint automatically.

If the ID column in the `users` table is named differently, pass its name as third
parameter to the [`foreign()`](#tableforeign) method:

```php
$this->db()->table( 'users_address', function( $table ) {
	$table->foreign( 'parentid', 'users', 'uid' );
} );
```

It's recommended to pass the name of the foreign key constraint as forth parameter
so it's easier to change or drop constraints later:

```php
$this->db()->table( 'users_address', function( $table ) {
	$table->foreign( 'parentid', 'users', 'id', 'fk_test_pid' );
} );
```

In case there's more than one column required to get the unique values required
by foreign keys, pass the column names as array:

```php
$this->db()->table( 'users_address', function( $table ) {
	$table->foreign( ['parentid', 'siteid'], 'users_address', ['id', 'siteid'] );
} );
```

Foreign key constraints can perform different actions if the referenced column
in the foreign table is deleted of updated. The standard action is to restrict
deleting the row or updating the referenced ID value. To change the behaviour,
use the [`onDelete()`](#foreignondelete) and [`onUpdate()`](#foreignonupdate)
methods:

```php
$this->db()->table( 'users_address', function( $table ) {
	$table->foreign( 'parentid', 'users' )->onDelete( 'SET NULL' )->onUpdate( 'RESTRICT' );
} );
```

There's a shortcut if you want to set both values to the same value:

```php
$this->db()->table( 'users_address', function( $table ) {
	$table->foreign( 'parentid', 'users' )->do( 'SET NULL' );
} );
```

Possible values for both methods are:

* CASCADE : Update referenced value
* NO ACTION : No change in referenced value (same as RESTRICT)
* RESTRICT : Forbid changing values
* SET DEFAULT : Set referenced value to the default value
* SET NULL : Set referenced value to NULL

The default action when deleting or updating rows is *CASCADE* so the values of
the foreign key column are updated to the same values as in the foreign table.

### Checking foreign key existence

To check if a foreign key already exists, use the [`hasForeign()`](#dbhasforeign) method:

```php
if( $this->db()->hasForeign( 'users_address', 'fk_usrad_parentid' ) ) {
    // The "fk_usrad_parentid" foreign key in the "users_address" table exists
}
```

It's also possible checking for several foreign key constraints at once. Then, the
[`hasForeign()`](#dbhasforeign) method will only return TRUE if all constraints
exist in the tables passed as first argument:

```php
if( $this->db()->hasForeign( 'users_address', ['fk_usrad_parentid', 'fk_usrad_siteid'] ) ) {
    // The "fk_usrad_parentid" and "fk_usrad_siteid" foreign keys exist in the "users_address" table
}
```

If a table object available, the [`hasForeign()`](#tablehasforeign) method of the
table can be used instead:

```php
$this->db()->table( 'users_address', function( $table ) {
	$table->hasForeign( 'fk_usrad_parentid' ) ) {
	    // The "fk_usrad_parentid" foreign key in the "users_address" table exists
	}
} );

$this->db()->table( 'users_address', function( $table ) {
	$table->hasForeign( ['fk_usrad_parentid', 'fk_usrad_siteid'] ) ) {
	    // The "fk_usrad_parentid" and "fk_usrad_siteid" foreign keys exist in the "users_address" table
	}
} );
```

In case you need the current values of an existing constraint:

```php
$this->db()->table( 'users_address', function( $table ) {
	$fk = $table->foreign( 'parentid', 'users' );

	// returns the name of the constraint
	$name = $fk->name()

	// returns the action when deleting rows
	$action = $fk->onDelete;

	// returns the action when updating the foreign ID
	$action = $fk->onUpdate;
} );
```

### Dropping foreign keys

To remove a foreign key constraint from a table, use the [`dropForeign()`](#dbdropforeign)
method and pass the name of the table and foreign key name as arguments:

```php
$this->db()->dropForeign( 'users_address', 'fk_usrad_parentid' );
```

You can also pass several foreign key names to drop them at once:

```php
$this->db()->dropForeign( 'users_address', ['fk_usrad_parentid', 'fk_usrad_siteid'] );
```

Within the anonymous function passed to the [`table()`](#dbtable) method, you
can also use the [`dropForeign()`](#tabledropforeign) method:

```php
$this->db()->table( 'users_address', function( $table ) {
	$table->dropForeign( 'fk_usrad_parentid' );
} );

$this->db()->table( 'users_address', function( $table ) {
	$table->dropForeign( ['fk_usrad_parentid', 'fk_usrad_siteid'] );
} );
```

### Foreign key methods

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
* NO ACTION : No change in referenced value (same as RESTRICT)
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
* NO ACTION : No change in referenced value (same as RESTRICT)
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


#### Foreign::do()

Sets the new value for the given Foreign key option

```php
public function do( string $action ) : self
```

* @param string `$action` Performed action
* @return self Same object for fluid method calls

Possible actions are:

* CASCADE : Delete or update referenced value
* NO ACTION : No change in referenced value (same as RESTRICT)
* RESTRICT : Forbid changing values
* SET DEFAULT : Set referenced value to the default value
* SET NULL : Set referenced value to NULL

**Examples:**

```php
$foreign->do( 'RESTRICT' );
```


#### Foreign::name()

* Sets the name of the constraint or returns the current name

```php
public function name( string $value = null )
```

* @param string&#124;null `$value` New name of the constraint or NULL to return current value
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

* @param string&#124;null `$value` Performed action or NULL to return current value
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
// same as
$foreign->opt( 'onDelete', 'SET NULL' );

$foreign->onDelete( 'SET NULL' )->onUpdate( 'SET NULL' );
```


#### Foreign::onUpdate()

* Sets the action if the referenced row is updated or returns the current value

```php
public function onUpdate( string $value = null )
```

* @param string&#124;null `$value` Performed action or NULL to return current value
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
// same as
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

### Adding sequences

A few database implementations offer sequences instead of auto-increment/identity
columns, namely Oracle and PostgreSQL. Sequences are functions which create
sequentially increasing numbers that are applied to a table column when inserting
new rows. To create a new sequence named *seq_test* use the [`sequence()`](#dbsequence)
method:

```php
$this->db()->sequence( 'seq_test' );
```

To use a different start value and step width than `1`, call the [`start()`](#sequencestart)
and [`step()`](#sequencestep) methods:

```php
$this->db()->sequence( 'seq_test', function( $seq ) {
	$seq->start( 1000 )->step( 2 );
} );
```

### Checking sequence existence

To check if a sequence already exists, use the [`hasSequence()`](#dbhassequence) method:

```php
if( $this->db()->hasSequence( 'seq_test' ) ) {
    // The "seq_test" sequence exists
}
```

It's also possible checking for several sequences at once. Then, the
[`hasSequence()`](#dbhassequence) method will only return TRUE if all sequences exist:

```php
if( $this->db()->hasSequence( ['seq_id', 'seq_test'] ) ) {
    // The "seq_id" and "seq_test" sequences exist
}
```

In case you need to know the current values of the table options:

```php
$this->db()->sequence( 'seq_test', function( $seq ) {
	// returns how many generated numbers are cached
	$cache = $seq->cache;

	// returns the number the sequence has started from
	$start = $seq->start;

	// returns the step width for newly generated numbers
	$step = $seq->step;
} );
```

### Dropping sequences

To remove a sequence, use the [`dropSequence()`](#dbdropsequence) method and
pass the name of the sequence as argument:

```php
$this->db()->dropSequence( 'seq_id' );
```

You can also pass several sequence names to drop them at once:

```php
$this->db()->dropSequence( ['seq_id', 'seq_test'] );
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

You can register custom methods that have access to the class properties of the
Upscheme Sequence object:

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

* @param int `$value` New number of sequence IDs cached by the client or NULL to return current value
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

* @param int `$value` New start value of the sequence or NULL to return current value
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

* @param int `$value` New step size the sequence is incremented or decremented by or NULL to return current value
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



## Indexes

Indexes speed up database queries and the time a query needs can drop from several
minutes to milliseconds if used correctly. There are several index types available:

* primary : All values must be unique, no NULL values and only one index per table is allowed
* unique : Values must be unique but NULL values are allowed (and more than once)
* index : Standard index with no restrictions
* spatial : Fast lookup in coordinates systems like geographic maps

All indexes can consist of one or more columns but the order of the columns has a
great impact if indexes are used for a query or not.

### Adding indexes

All indexes are bound to the table which contains the columns the index covers.
The simplest way to create an index over a single column is to use the
[`index()`](#columnindex) method of the column object:

```php
$this->db()->table( 'test', function( $table ) {
	$table->string( 'label' )->index();
} );
```

The second parameter of the [`index()`](#columnindex) method allows you to set
a custom name for the index:

```php
$this->db()->table( 'test', function( $table ) {
	$table->string( 'label' )->index( 'idx_test_label' );
} );
```

**Note:** For a maximum compatibility between different database types, the
length of the index names should be 30 characters or less.

The same is possible for primary, unique and spatial indexes:

```php
$this->db()->table( 'test', function( $table ) {
	// primary key
	$table->int( 'id' )->primary();
	$table->int( 'id' )->primary( 'pk_test_id' ); // ignored by MySQL, MariaDB, etc.

	// unique key
	$table->string( 'code' )->unique();
	$table->string( 'code' )->unique( 'unq_test_code' );

	// spatial index
	$table->col( 'location', 'point' )->spatial();
	$table->col( 'location', 'point' )->spatial( 'idx_test_location' );
} );
```

For multi-column indexes, the [`primary()`](#tableprimary), [`unique()`](#tableunique)
and [`index()`](#tableindex) methods are available in the table object:

```php
$this->db()->table( 'test', function( $table ) {
	// primary composite index
	$table->primary( ['siteid', 'code'] );

	// unique composite index
	$table->unique( ['parentid', 'type'] );

	// regular composite index
	$table->index( ['label', 'status'] );
} );
```

Spatial indexes can NOT span multiple columns but creating them is also possible
using the [`spatial()`](#tablespatial) method of the table object:

```php
$this->db()->table( 'test', function( $table ) {
	$table->spatial( 'location' );
} );
```

### Checking index existence

To check if an index already exists, use the [`hasIndex()`](#dbhasindex) method:

```php
if( $this->db()->hasIndex( 'users', 'idx_users_name' ) ) {
    // The "idx_users_name" index in the "users" table exists
}
```

You can check for several indexes at once too. In that case, the
[`hasIndex()`](#dbhasindex) method will only return TRUE if all indexes exist:

```php
if( $this->db()->hasIndex( 'users', ['idx_users_name', 'idx_users_status'] ) ) {
    // The "idx_users_name" and "idx_users_status" indexes in the "users" table exists
}
```

If you already have a table object, you can use [`hasIndex()`](#tablehasindex) as well:

```php
if( $table->hasIndex( 'idx_users_name' ) ) {
    // The "idx_users_name" index in the table exists
}

if( $table->hasIndex( ['idx_users_name', 'idx_users_status'] ) ) {
    // The "idx_users_name" and "idx_users_status" indexes in the table exists
}
```

### Renaming indexes

To rename indexes directly, using the [`renameIndex()`](#dbrenameindex) method
of the DB schema:

```php
// single index
$this->db()->renameIndex( 'testtable', 'idx_test_label', 'idx_test_name' );

// multiple indexes
$this->db()->renameIndex( 'testtable', ['idx_test_label' => 'idx_test_name', 'idx_text_stat' => 'idx_test_status'] );
```

If a table object is already available, you can use its [`renameIndex()`](#tablerenameindex)
method to rename one or more indexes:

```php
$this->db()->table( 'test', function( $table ) {
	// single index
	$table->renameIndex( 'idx_test_label', 'idx_test_name' );

	// multiple indexes
	$table->renameIndex( ['idx_test_label' => 'idx_test_name', 'idx_text_stat' => 'idx_test_status'] );
} );
```

### Dropping indexes

To drop indexes, use the [`dropIndex()`](#dbdropindex) method from the DB schema object:

```php
$this->db()->dropIndex( 'users', 'idx_test_name' );
```

You can drop several indexes at once if you pass the name of all indexes you want
to drop as array:

```php
$this->db()->dropIndex( 'users', ['idx_test_name', 'idx_test_status'] );
```

If you already have a table object, you can use [`dropIndex()`](#tabledropindex) too:

```php
// single index
$table->dropIndex( 'idx_test_name' );

// multiple indexes
$table->dropIndex( ['idx_test_name', 'idx_test_status'] );
```

In all cases, indexes are only removed if they exist. No error is reported if one
or more indexes doesn't exist in the table.

### Custom index naming

It's not necessary to pass a custom index name when creating new indexes. Then,
the index name is generated automatically but their name will consist of a hash
that is hard to read. Also, you don't know which columns the indexes span from the
index name.

Upscheme allows you to add your own naming function for indexes which is used if
not index name is passed to the methods for creating indexes. Before running the
migrations, register your nameing function using the [`macro()`](#macro)
method in the table objects:

```php
use \Aimeos\Upscheme\Schema\Table;

Table::marco( 'nameIndex', function( string $table, array $columns, string $type ) {
	return $type . '_' . $table . '_' . join( '_', $columns );
} );

\Aimeos\Upscheme\Up::use( $config, './migrations/' )->up()
```

For a table "testtable", a column "label" and the type "idx", this will return
*idx_testtable_label* instead of a hash.

Available index types are:

* idx : Regular and spatial indexes
* fk : Foreign key index
* pk : Primary key index
* unq : Unique index

**Note:** For compatibility to all supported database types, the maximum length
of the index names must be not longer than 30 characters!



## Customizing Upscheme

### Adding custom methods

You can add new methods to all Upscheme objects using the `macro()` method. Each
custom method has access to the class properties and methods of the class it's
registered for including the Doctrine DBAL objects.

To register a method named `test()` in the DB schema object with two parameters
`$arg1` and `$arg2` which has access to the same class properties as the DB
[`__call()`](#db__call) method use:

```php
\Aimeos\Upscheme\Schema\DB::marco( 'test', function( $arg1, $arg2 ) {
	// $this->conn : Doctrine connection
	// $this->from : Doctrine start schema
	// $this->to : Doctrine current schema
	// $this->up : Upscheme object
	// return $this or a value
} );

$db->test( 'key', 'value' );
```

Registering a method `test()` in the Table schema object with one parameter `$arg1`
which has access to the same class properties as the Table [`__call()`](#table__call)
method use:

```php
\Aimeos\Upscheme\Schema\Table::marco( 'test', function( $arg1 ) {
	// $this->db : Upscheme DB object
	// $this->table : Doctrine Table object
	// return $this or a value
} );

$table->test( 'something' );
```

Same for a method `test()` in the Column schema object with an optional parameter
`$value` which has access to the same class properties as the Column
[`__call()`](#column__call) method use:

```php
\Aimeos\Upscheme\Schema\Column::marco( 'test', function( $value = null ) {
	// $this->db : Upscheme DB object
	// $this->table : Upscheme Table object
	// $this->column : Doctrine Column object
	// return $this or a value
} );

$column->test();
```

To extend the Foreign object for foreign key constraints with a `test()` method
with no parameter having access to the same class properties as the Foreign
[`__call()`](#foreign__call) method use:

```php
\Aimeos\Upscheme\Schema\Foreign::marco( 'test', function() {
	// $this->table : Upscheme Table object
	// $this->dbaltable : Doctrine Table object
	// $this->localcol : Array of local column names
	// $this->fktable : Foreign table name
	// $this->fkcol : Foreign table column names
	// $this->name : Foreign key name
	// $this->opts : Array of foreign key options ("onDelete" and "onUpdate")
	// return $this or a value
} );

$foreign->test();
```

Finally, extending the Sequence object with a `test()` method having no parameters
and access to the same class properties as the Sequence [`__call()`](#sequence__call)
method use:

```php
\Aimeos\Upscheme\Schema\Sequence::marco( 'test', function() {
	// $this->db : Upscheme DB object
	// $this->sequence : Doctrine Sequence object
	// return $this or a value
} );

$sequence->test();
```

### Implementing custom columns

Instead of calling the [`col()`](#tablecol) method of the Table object with all
parameters and modifiers each time, you can create your own shortcut methods, e.g.:

```php
\Aimeos\Upscheme\Schema\Table::marco( 'utinyint', function( string $name ) {
	return $this->col( $name, 'tinyint' )->unsigned( true );
} );
```

It's also possible to create several columns at once if you want to add them to
several tables:

```php
\Aimeos\Upscheme\Schema\Table::marco( 'defaults', function() {
	$this->id();
	$this->datetime( 'ctime' );
	$this->datetime( 'mtime' );
	$this->string( 'editor' );
	return $this;
} );
```

Then, use your custom methods when creating or updating tables:

```php
$this->db()->table( 'test', function( $table ) {
	$table->defaults();
	$table->utinyint( 'status' );
} );
```
