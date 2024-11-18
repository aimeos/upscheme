<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

namespace Aimeos\Upscheme\Schema;


class DBTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $connmock;
	private $pfmock;
	private $schemamock;
	private $smmock;
	private $tablemock;
	private $upmock;


	protected function setUp() : void
	{
		$this->upmock = $this->getMockBuilder( '\Aimeos\Upscheme\Up' )
			->disableOriginalConstructor()
			->getMock();

		$this->connmock = $this->getMockBuilder( '\Doctrine\DBAL\Connection' )
			->disableOriginalConstructor()
			->getMock();

		$this->pfmock = $this->getMockBuilder( '\Doctrine\DBAL\Platforms\MySQLPlatform' )
			->disableOriginalConstructor()
			->getMock();

		$this->smmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\AbstractSchemaManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemamock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Schema' )
			->disableOriginalConstructor()
			->disableOriginalClone()
			->getMock();

		$this->tablemock = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();


		$this->connmock->expects( $this->any() )->method( 'createSchemaManager' )
			->willReturn( $this->smmock );

		$this->connmock->expects( $this->any() )->method( 'quoteIdentifier' )
			->willReturnCallback( function( $value ) {
				return '"' . $value . '"';
			} );

		$this->connmock->expects( $this->any() )->method( 'getDatabasePlatform' )
			->willReturn( $this->pfmock );

		$this->smmock->expects( $this->any() )->method( 'introspectSchema' )
			->willReturn( $this->schemamock );


		$this->object = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\DB' )
			->setConstructorArgs( [$this->upmock, $this->connmock] )
			->onlyMethods( ['table', 'up', 'getColumnSQL', 'getViews'] )
			->getMock();

		$this->object->expects( $this->any() )->method( 'table' )->willReturn( $this->tablemock );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->tablemock, $this->schemamock, $this->smmock, $this->connmock, $this->upmock );
	}


	public function testCall()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasNamespace' );
		$this->object->hasNamespace( 'test' );
	}


	public function testCallMacro()
	{
		\Aimeos\Upscheme\Schema\DB::macro( 'unittest', function() { return 'yes'; } );
		$this->assertEquals( 'yes', $this->object->unittest() );
	}


	public function testClone()
	{
		$this->connmock->expects( $this->once() )->method( 'close' );
		$this->object->expects( $this->once() )->method( 'up' );

		$obj = clone $this->object;
	}


	public function testClose()
	{
		$this->connmock->expects( $this->once() )->method( 'close' );
		$this->object->close();
	}


	public function testDelete()
	{
		$this->connmock->expects( $this->once() )->method( 'delete' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->delete( 'unittest' ) );
	}


	public function testDropColumn()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'dropColumn' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropColumn( 'unit', 'test' ) );
	}


	public function testDropColumnMultiple()
	{
		$this->schemamock->expects( $this->exactly( 2 ) )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'hasColumn' )->willReturn( true );
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'dropColumn' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropColumn( 'unit', ['test', 'test2'] ) );
	}


	public function testDropForeign()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'hasForeign' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'dropForeign' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropForeign( 'unit', 'test' ) );
	}


	public function testDropForeignMultiple()
	{
		$this->schemamock->expects( $this->exactly( 2 ) )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'hasForeign' )->willReturn( true );
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'dropForeign' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropForeign( 'unit', ['test', 'test2'] ) );
	}


	public function testDropIndex()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'dropIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropIndex( 'unit', 'test' ) );
	}


	public function testDropIndexMultiple()
	{
		$this->schemamock->expects( $this->exactly( 2 ) )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'dropIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropIndex( 'unit', ['test', 'test2'] ) );
	}


	public function testDropSequence()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasSequence' )->willReturn( true );
		$this->schemamock->expects( $this->once() )->method( 'dropSequence' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropSequence( 'unit', 'test' ) );
	}


	public function testDropSequenceMultiple()
	{
		$this->schemamock->expects( $this->exactly( 2 ) )->method( 'hasSequence' )->willReturn( true );
		$this->schemamock->expects( $this->exactly( 2 ) )->method( 'dropSequence' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropSequence( ['test', 'test2'] ) );
	}


	public function testDropTable()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );

		if( $this->object->type() !== 'oracle' ) {
			$this->schemamock->expects( $this->once() )->method( 'dropTable' );
		} else {
			$this->smmock->expects( $this->once() )->method( 'dropTable' );
		}

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropTable( 'unit', 'test' ) );
	}


	public function testDropTableMultiple()
	{
		$this->schemamock->expects( $this->exactly( 2 ) )->method( 'hasTable' )->willReturn( true );

		if( $this->object->type() !== 'oracle' ) {
			$this->schemamock->expects( $this->exactly( 2 ) )->method( 'dropTable' );
		} else {
			$this->smmock->expects( $this->exactly( 2 ) )->method( 'dropTable' );
		}

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropTable( ['test', 'test2'] ) );
	}


	public function testDropView()
	{
		$this->object->expects( $this->once() )->method( 'getViews' )->willReturn( ['test' => new \stdClass] );
		$this->smmock->expects( $this->once() )->method( 'dropView' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropView( 'test' ) );
	}


	public function testDropViewMultiple()
	{
		$this->object->expects( $this->exactly( 2 ) )->method( 'getViews' )->willReturn( ['test' => new \stdClass, 'test2' => new \stdClass] );
		$this->smmock->expects( $this->exactly( 2 ) )->method( 'dropView' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->dropView( ['test', 'test2'] ) );
	}


	public function testExec()
	{
		$this->connmock->expects( $this->once() )->method( 'executeStatement' )->willReturn( 123 );

		$this->assertEquals( 123, $this->object->exec( 'test' ) );
	}


	public function testFor()
	{
		$this->connmock->expects( $this->once() )->method( 'executeStatement' )->willReturn( 123 );

		$this->assertEquals( 123, $this->object->exec( 'test' ) );
	}


	public function testForMultiple()
	{
		$this->connmock->expects( $this->exactly( 2 ) )->method( 'executeStatement' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->for( 'mysql', ['test', 'test2'] ) );
	}


	public function testForMismatch()
	{
		$this->connmock->expects( $this->never() )->method( 'executeStatement' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->for( 'postgresql', 'test' ) );
	}


	public function testHasColumn()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );

		$this->assertTrue( $this->object->hasColumn( 'unit', 'test' ) );
	}


	public function testHasColumnNot()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( false );
		$this->assertFalse( $this->object->hasColumn( 'unit', 'test' ) );
	}


	public function testHasForeign()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'hasForeign' )->willReturn( true );

		$this->assertTrue( $this->object->hasForeign( 'unit', 'test' ) );
	}


	public function testHasForeignNot()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( false );
		$this->assertFalse( $this->object->hasForeign( 'unit', 'test' ) );
	}


	public function testHasIndex()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );

		$this->assertTrue( $this->object->hasIndex( 'unit', 'test' ) );
	}


	public function testHasIndexNot()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( false );
		$this->assertFalse( $this->object->hasIndex( 'unit', 'test' ) );
	}


	public function testHasSequence()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasSequence' )->willReturn( true );
		$this->assertTrue( $this->object->hasSequence( 'unit', 'test' ) );
	}


	public function testHasSequenceNot()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasSequence' )->willReturn( false );
		$this->assertFalse( $this->object->hasSequence( 'unit', 'test' ) );
	}


	public function testHasTable()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->assertTrue( $this->object->hasTable( 'unit', 'test' ) );
	}


	public function testHasTableNot()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( false );
		$this->assertFalse( $this->object->hasTable( 'unit', 'test' ) );
	}


	public function testHasView()
	{
		$this->object->expects( $this->once() )->method( 'getViews' )->willReturn( ['test' => new \stdClass] );
		$this->assertTrue( $this->object->hasView( 'test' ) );
	}


	public function testHasViewNot()
	{
		$this->object->expects( $this->once() )->method( 'getViews' )->willReturn( [] );
		$this->assertFalse( $this->object->hasView( 'test' ) );
	}


	public function testInsert()
	{
		$this->connmock->expects( $this->once() )->method( 'insert' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->insert( 'unittest', [] ) );
	}


	public function testLastId()
	{
		$this->connmock->expects( $this->once() )->method( 'lastInsertId' )->willReturn( '123' );
		$this->assertEquals( '123', $this->object->lastId() );
	}


	public function testName()
	{
		$this->schemamock->expects( $this->once() )->method( 'getName' )->willReturn( 'testdb' );
		$this->assertEquals( 'testdb', $this->object->name() );
	}


	public function testQ()
	{
		$this->connmock->expects( $this->once() )->method( 'quote' )->willReturn( '123' );
		$this->assertEquals( '123', $this->object->q( 123 ) );
	}


	public function testQi()
	{
		$this->connmock->expects( $this->once() )->method( 'quoteIdentifier' )->willReturn( '"key"' );
		$this->assertEquals( '"key"', $this->object->qi( 'key' ) );
	}


	public function testQuery()
	{
		$mock = $this->getMockBuilder( '\Doctrine\DBAL\Result' )
			->disableOriginalConstructor()
			->getMock();

		$this->connmock->expects( $this->once() )->method( 'executeQuery' )->willReturn( $mock );

		$this->assertInstanceOf( \Doctrine\DBAL\Result::class, $this->object->query( 'test' ) );
	}


	public function testRenameColumn()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );
		$this->connmock->expects( $this->any() )->method( 'quoteIdentifier' )->willReturnArgument( 0 );
		$this->object->expects( $this->any() )->method( 'getColumnSQL' )->willReturn( 'test INTEGER' );
		$this->connmock->expects( $this->once() )->method( 'executeStatement' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->renameColumn( 'table', 'unit', 'test' ) );
	}


	public function testRenameColumnMultiple()
	{
		$this->schemamock->expects( $this->exactly( 2 ) )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'hasColumn' )->willReturn( true );
		$this->connmock->expects( $this->any() )->method( 'quoteIdentifier' )->willReturnArgument( 0 );
		$this->object->expects( $this->any() )->method( 'getColumnSQL' )->willReturn( 'test INTEGER' );
		$this->connmock->expects( $this->exactly( 2 ) )->method( 'executeStatement' );

		$cols = ['unit' => 'test', 'unit2' => 'test2'];
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->renameColumn( 'table', $cols ) );
	}


	public function testRenameColumnException()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );

		$this->expectException( \RuntimeException::class );
		$this->object->renameColumn( 'table', 'unit' );
	}


	public function testRenameIndex()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->object->expects( $this->once() )->method( 'table' )->willReturn( $this->tablemock );
		$this->tablemock->expects( $this->once() )->method( 'renameIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->renameIndex( 'table', 'unit', 'test' ) );
	}


	public function testRenameTable()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->smmock->expects( $this->once() )->method( 'renameTable' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->renameTable( 'unit', 'test' ) );
	}


	public function testRenameTableMultiple()
	{
		$this->schemamock->expects( $this->exactly( 2 ) )->method( 'hasTable' )->willReturn( true );
		$this->smmock->expects( $this->exactly( 2 ) )->method( 'renameTable' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->renameTable( ['test', 'test2'] ) );
	}


	public function testRenameTableException()
	{
		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );

		$this->expectException( \RuntimeException::class );
		$this->object->renameTable( 'unit' );
	}


	public function testReset()
	{
		$this->smmock->expects( $this->once() )->method( 'introspectSchema' )
			->willReturn( $this->schemamock );

		$this->object->reset();
	}


	public function testSequence()
	{
		$seqmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Sequence' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemamock->expects( $this->once() )->method( 'hasSequence' )->willReturn( true );
		$this->schemamock->expects( $this->once() )->method( 'getSequence' )->willReturn( $seqmock );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Sequence::class, $this->object->sequence( 'unittest' ) );
	}


	public function testSequenceNew()
	{
		$seqmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Sequence' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemamock->expects( $this->once() )->method( 'hasSequence' )->willReturn( false );
		$this->schemamock->expects( $this->once() )->method( 'createSequence' )->willReturn( $seqmock );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Sequence::class, $this->object->sequence( 'unittest' ) );
	}


	public function testSequenceClosure()
	{
		$seqmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Sequence' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemamock->expects( $this->once() )->method( 'hasSequence' )->willReturn( false );
		$this->schemamock->expects( $this->once() )->method( 'createSequence' )->willReturn( $seqmock );

		$fcn = function( $seq ) {};

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Sequence::class, $this->object->sequence( 'unittest', $fcn ) );
	}


	public function testStmt()
	{
		$qbmock = $this->getMockBuilder( '\Doctrine\DBAL\Query\QueryBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->connmock->expects( $this->once() )->method( 'createQueryBuilder' )->willReturn( $qbmock );

		$this->assertInstanceOf( \Doctrine\DBAL\Query\QueryBuilder::class, $this->object->stmt() );
	}


	public function testTable()
	{
		$tablemock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();

		$object = new \Aimeos\Upscheme\Schema\DB( $this->upmock, $this->connmock );

		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->schemamock->expects( $this->once() )->method( 'getTable' )->willReturn( $tablemock );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $object->table( 'unittest' ) );
	}


	public function testTableNew()
	{
		$tablemock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();

		$object = new \Aimeos\Upscheme\Schema\DB( $this->upmock, $this->connmock );

		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( false );
		$this->schemamock->expects( $this->once() )->method( 'createTable' )->willReturn( $tablemock );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $object->table( 'unittest' ) );
	}


	public function testTableClosure()
	{
		$tablemock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();

		$object = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\DB' )
			->setConstructorArgs( [$this->upmock, $this->connmock] )
			->onlyMethods( ['up'] )
			->getMock();

		$this->schemamock->expects( $this->once() )->method( 'hasTable' )->willReturn( false );
		$this->schemamock->expects( $this->once() )->method( 'createTable' )->willReturn( $tablemock );

		$fcn = function( $seq ) {};

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $object->table( 'unittest', $fcn ) );
	}


	public function testTransaction()
	{
		$object = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\DB' )
			->setConstructorArgs( [$this->upmock, $this->connmock] )
			->onlyMethods( ['up'] )
			->getMock();

		$fcn = function( $db ) {};

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $object->transaction( $fcn ) );
	}


	public function testTransactionException()
	{
		$object = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\DB' )
			->setConstructorArgs( [$this->upmock, $this->connmock] )
			->onlyMethods( ['up'] )
			->getMock();

		$fcn = function( $db ) {
			throw new \Exception();
		};

		$this->expectException( \Exception::class );
		$object->transaction( $fcn );
	}


	public function testType()
	{
		$this->assertEquals( 'mysql', $this->object->type() );
	}


	public function testUpdate()
	{
		$this->connmock->expects( $this->once() )->method( 'update' );
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $this->object->update( 'unittest', [] ) );
	}


	public function testView()
	{
		$view = new class {
			public function getNamespaceName() { return ''; }
			public function getShortestName() { return 'unittest'; }
		};

		$object = new \Aimeos\Upscheme\Schema\DB( $this->upmock, $this->connmock );

		$this->smmock->expects( $this->once() )->method( 'listViews' )->willReturn( [$view] );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $object->view( 'unittest', 'CREATE VIEW' ) );
	}


	public function testViewNew()
	{
		$object = new \Aimeos\Upscheme\Schema\DB( $this->upmock, $this->connmock );

		$this->smmock->expects( $this->once() )->method( 'listViews' )->willReturn( [] );
		$this->smmock->expects( $this->once() )->method( 'createView' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\DB::class, $object->view( 'unittest', 'CREATE VIEW' ) );
	}
}
