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
