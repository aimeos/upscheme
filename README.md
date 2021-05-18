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

## Why Upscheme

Migrations are like version control for your database. They allow you to record all changes and share them with others so they get the exact same state in their installation.

For upgrading relational database schemas, two packages are currently used most often: Doctrine DBAL and Doctrine migrations. While Doctrine DBAL does a good job in abstracting the differences of several database implementations, it's API requires writing a lot of code. Doctrine migrations on the other site has some drawbacks which make it hard to use in all applications that support 3rd party extensions.

### Doctrine DBAL drawbacks

The API of DBAL is very verbose and you need to write lots of code even for simple things. Upscheme uses Doctrine DBAL to offer an easy to use API for upgrading the database schema of your application with minimal code. Let's compare some example code you have to write for DBAL and for Upscheme in a migration.

DBAL:
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

Upscheme:
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

If you didn't use Doctrine DBAL before, your database configuration may have a different structure and/or use different values for the database type. Upscheme allows you to register a custom method that transforms your configration intovalid DBAL settings, e.g.:

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
