<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

namespace Aimeos\Upscheme\Schema;


class TableTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $dbmock;
	private $tablemock;


	protected function setUp() : void
	{
		$this->dbmock = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\DB' )
			->disableOriginalConstructor()
			->getMock();

		$this->dbmock->expects( $this->any() )->method( 'qi' )
			->willReturnCallback( function( $value ) {
				return '"' . $value . '"';
			} );

		$methods = [
			'addIndex', 'getIndex', 'getIndexes', 'hasIndex', 'renameIndex', 'dropIndex',
			'dropPrimaryKey', 'getPrimaryKey', 'setPrimaryKey',
			'addUniqueIndex', 'hasUniqueConstraint', 'removeUniqueConstraint',
			'getName', 'addOption', 'getOption', 'hasOption',
			'dropColumn', 'hasColumn',
			'hasForeignKey', 'removeForeignKey',
		];

		$this->tablemock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->disableOriginalConstructor()
			->onlyMethods( $methods )
			->getMock();

		$this->object = new \Aimeos\Upscheme\Schema\Table( $this->dbmock, $this->tablemock );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->tablemock, $this->dbmock );
	}


	public function testCall()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasForeignKey' );

		$this->object->hasForeignKey( 'unittest' );
	}


	public function testCallMacro()
	{
		\Aimeos\Upscheme\Schema\Table::macro( 'unittest', function() { return 'yes'; } );

		$this->assertEquals( 'yes', $this->object->unittest() );
	}


	public function testGetMagic()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasOption' )
			->willReturn( true );

		$this->tablemock->expects( $this->once() )->method( 'getOption' )
			->willReturn( 'yes' );

		$this->assertEquals( 'yes', $this->object->unittest );
	}


	public function testSetMagic()
	{
		$this->tablemock->expects( $this->once() )->method( 'addOption' );

		$this->object->unittest = 'yes';
	}


	public function testOptGet()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasOption' )
			->willReturn( true );

		$this->tablemock->expects( $this->once() )->method( 'getOption' )
			->willReturn( 'yes' );

		$this->assertEquals( 'yes', $this->object->opt( 'unittest' ) );
	}


	public function testOptSet()
	{
		$this->tablemock->expects( $this->once() )->method( 'addOption' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->opt( 'unittest', 'yes' ) );
	}


	public function testBigid()
	{
		\Aimeos\Upscheme\Schema\Table::macro( 'bigid', function( string $name = null ) : Column {
			return $this->bigint( $name ?: 'id' )->seq( true )->primary();
		} );

		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );

		$col = $this->object->bigid();

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'id', $col->name() );
		$this->assertEquals( 'bigint', $col->type() );
		$this->assertTrue( $col->seq() );
	}


	public function testBigidName()
	{
		\Aimeos\Upscheme\Schema\Table::macro( 'bigid', function( string $name = null ) : Column {
			return $this->bigint( $name ?: 'id' )->seq( true )->primary();
		} );

		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );

		$col = $this->object->bigid( 'uid' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'uid', $col->name() );
		$this->assertEquals( 'bigint', $col->type() );
		$this->assertTrue( $col->seq() );
	}


	public function testBigint()
	{
		$col = $this->object->bigint( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'bigint', $col->type() );
		$this->assertEquals( 0, $col->default() );
	}


	public function testBinary()
	{
		$col = $this->object->binary( 'unittest', 255 );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'binary', $col->type() );
		$this->assertEquals( 255, $col->length() );
		$this->assertEquals( '', $col->default() );
	}


	public function testBlob()
	{
		$col = $this->object->blob( 'unittest', 0x7fff );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'blob', $col->type() );
		$this->assertEquals( 0x7fff, $col->length() );
		$this->assertEquals( '', $col->default() );
	}


	public function testBool()
	{
		$col = $this->object->bool( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'boolean', $col->type() );
		$this->assertEquals( false, $col->default() );
	}


	public function testBoolean()
	{
		$col = $this->object->boolean( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'boolean', $col->type() );
		$this->assertEquals( false, $col->default() );
	}


	public function testChar()
	{
		$col = $this->object->char( 'unittest', 3 );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'string', $col->type() );
		$this->assertEquals( true, $col->fixed() );
		$this->assertEquals( 3, $col->length() );
	}


	public function testCol()
	{
		$col = $this->object->col( 'unittest', 'integer' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'integer', $col->type() );


		$this->tablemock->expects( $this->any() )->method( 'hasColumn' )->willReturn( true );

		$col = $this->object->col( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'integer', $col->type() );


		$this->tablemock->expects( $this->any() )->method( 'hasColumn' )->willReturn( true );

		$col = $this->object->col( 'unittest', 'bigint' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'bigint', $col->type() );
	}


	public function testDate()
	{
		$col = $this->object->date( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'date', $col->type() );
	}


	public function testDatetime()
	{
		$col = $this->object->datetime( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'datetime', $col->type() );
	}


	public function testDatetimetz()
	{
		$col = $this->object->datetimetz( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'datetimetz', $col->type() );
	}


	public function testDecimal()
	{
		$col = $this->object->decimal( 'unittest', 10, 3 );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'decimal', $col->type() );
		$this->assertEquals( 10, $col->precision() );
		$this->assertEquals( 3, $col->scale() );
		$this->assertEquals( 0, $col->default() );
	}


	public function testFloat()
	{
		$col = $this->object->float( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'float', $col->type() );
		$this->assertEquals( 0, $col->default() );
	}


	public function testGuid()
	{
		$col = $this->object->guid( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'guid', $col->type() );
	}


	public function testId()
	{
		\Aimeos\Upscheme\Schema\Table::macro( 'id', function( string $name = null ) : Column {
			return $this->integer( $name ?: 'id' )->seq( true )->primary();
		} );

		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );

		$col = $this->object->id();

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'id', $col->name() );
		$this->assertEquals( 'integer', $col->type() );
		$this->assertTrue( $col->seq() );
	}


	public function testIdName()
	{
		\Aimeos\Upscheme\Schema\Table::macro( 'id', function( string $name = null ) : Column {
			return $this->integer( $name ?: 'id' )->seq( true )->primary();
		} );

		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );

		$col = $this->object->id( 'uid' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'uid', $col->name() );
		$this->assertEquals( 'integer', $col->type() );
		$this->assertTrue( $col->seq() );
	}


	public function testInt()
	{
		$col = $this->object->int( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'integer', $col->type() );
		$this->assertEquals( 0, $col->default() );
	}


	public function testInteger()
	{
		$col = $this->object->int( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'integer', $col->type() );
		$this->assertEquals( 0, $col->default() );
	}


	public function testJson()
	{
		$col = $this->object->json( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'json', $col->type() );
	}


	public function testName()
	{
		$this->tablemock->expects( $this->once() )->method( 'getName' )
			->willReturn( 'unittest' );

		$this->assertEquals( 'unittest', $this->object->name() );
	}


	public function testSmallint()
	{
		$col = $this->object->smallint( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'smallint', $col->type() );
		$this->assertEquals( 0, $col->default() );
	}


	public function testString()
	{
		$col = $this->object->string( 'unittest', 128 );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'string', $col->type() );
		$this->assertEquals( 128, $col->length() );
		$this->assertEquals( '', $col->default() );
	}


	public function testText()
	{
		$col = $this->object->text( 'unittest', 0x7fff );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'text', $col->type() );
		$this->assertEquals( 0x7fff, $col->length() );
		$this->assertEquals( '', $col->default() );
	}


	public function testTime()
	{
		$col = $this->object->time( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'time', $col->type() );
	}


	public function testUuid()
	{
		$col = $this->object->uuid( 'unittest' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $col );
		$this->assertEquals( 'unittest', $col->name() );
		$this->assertEquals( 'guid', $col->type() );
	}


	public function testDropColumn()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'dropColumn' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->dropColumn( 'unittest' ) );
	}


	public function testDropIndex()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'dropIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->dropIndex( 'unittest' ) );
	}


	public function testDropForeign()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasForeignKey' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'removeForeignKey' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->dropForeign( 'unittest' ) );
	}


	public function testDropPrimary()
	{
		$idx = new \Doctrine\DBAL\Schema\Index( 'PRIMARY', ['id'], true, true );
		$this->tablemock->expects( $this->once() )->method( 'getPrimaryKey' )->willReturn( $idx );
		$this->tablemock->expects( $this->once() )->method( 'dropPrimaryKey' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->dropPrimary( 'unittest' ) );
	}


	public function testHasColumn()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );
		$this->assertTrue( $this->object->hasColumn( 'unittest' ) );
	}


	public function testHasColumnNot()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasColumn' )->willReturn( false );
		$this->assertFalse( $this->object->hasColumn( 'unittest' ) );
	}


	public function testHasColumnMultiple()
	{
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'hasColumn' )->willReturn( true );
		$this->assertTrue( $this->object->hasColumn( ['unittest', 'testunit'] ) );
	}


	public function testHasIndex()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );
		$this->assertTrue( $this->object->hasIndex( 'unittest' ) );
	}


	public function testHasIndexNot()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( false );
		$this->assertFalse( $this->object->hasIndex( 'unittest' ) );
	}


	public function testHasIndexMultiple()
	{
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'hasIndex' )->willReturn( true );
		$this->assertTrue( $this->object->hasIndex( ['unittest', 'testunit'] ) );
	}


	public function testHasForeign()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasForeignKey' )->willReturn( true );
		$this->assertTrue( $this->object->hasForeign( 'unittest' ) );
	}


	public function testHasForeignNot()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasForeignKey' )->willReturn( false );
		$this->assertFalse( $this->object->hasForeign( 'unittest' ) );
	}


	public function testHasForeignMultiple()
	{
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'hasForeignKey' )->willReturn( true );
		$this->assertTrue( $this->object->hasForeign( ['unittest', 'testunit'] ) );
	}


	public function testForeign()
	{
		$dbalcol = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Column' )
			->disableOriginalConstructor()
			->getMock();

		$dbaltable = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();

		$table = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Table' )
			->onlyMethods( ['copyColumn', 'hasColumn'] )
			->setConstructorArgs( [$this->dbmock, $dbaltable] )
			->getMock();

		$table->expects( $this->once() )->method( 'copyColumn' );
		$table->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );
		$dbaltable->expects( $this->once() )->method( 'getColumn' )->willReturn( $dbalcol );

		$this->dbmock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->dbmock->expects( $this->once() )->method( 'table' )->willReturn( $table );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Foreign::class, $table->foreign( 'pid', 'fktable', 'id', 'fk_pid' ) );
	}


	public function testForeignTableMissing()
	{
		$this->expectException( 'RuntimeException' );
		$this->object->foreign( 'pid', 'fktable', 'id', 'fk_pid' );
	}


	public function testForeignColumnMissing()
	{
		$this->dbmock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );

		$this->expectException( 'RuntimeException' );
		$this->object->foreign( 'pid', 'fktable', 'id', 'fk_pid' );
	}


	public function testForeignLocalcolMissing()
	{
		$dbalcol = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Column' )
			->disableOriginalConstructor()
			->getMock();

		$dbaltable = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();

		$table = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Table' )
			->onlyMethods( ['copyColumn', 'hasColumn'] )
			->setConstructorArgs( [$this->dbmock, $dbaltable] )
			->getMock();

		$table->expects( $this->once() )->method( 'copyColumn' );
		$table->expects( $this->exactly( 2 ) )->method( 'hasColumn' )->willReturn( true );
		$dbaltable->expects( $this->once() )->method( 'getColumn' )->willReturn( $dbalcol );

		$this->dbmock->expects( $this->once() )->method( 'hasTable' )->willReturn( true );
		$this->dbmock->expects( $this->once() )->method( 'table' )->willReturn( $table );

		$this->expectException( 'LogicException' );
		$table->foreign( ['pid'], 'fktable', ['id', 'sid'], 'fk_pid' );
	}


	public function testIndex()
	{
		$this->tablemock->expects( $this->once() )->method( 'addIndex' );
		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );
		$this->tablemock->expects( $this->once() )->method( 'getIndexes' )->willReturn( [] );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->index( 'pid' ) );
	}


	public function testIndexName()
	{
		$this->tablemock->expects( $this->once() )->method( 'addIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->index( 'pid', 'idx_pid' ) );
	}


	public function testIndexExists()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['getColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'getColumns' )->willReturn( ['pid'] );
		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );
		$this->tablemock->expects( $this->once() )->method( 'getIndexes' )->willReturn( [$idxmock] );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->index( 'pid' ) );
	}


	public function testIndexExistsName()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['spansColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'spansColumns' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'getIndex' )->willReturn( $idxmock );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->index( 'pid', 'idx_pid' ) );
	}


	public function testIndexChange()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['spansColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'spansColumns' )->willReturn( false );
		$this->tablemock->expects( $this->once() )->method( 'getIndex' )->willReturn( $idxmock );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'dropIndex' );
		$this->tablemock->expects( $this->once() )->method( 'addIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->index( 'pid', 'idx_pid' ) );
	}


	public function testPrimary()
	{
		$this->tablemock->expects( $this->once() )->method( 'setPrimaryKey' );
		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->primary( 'id' ) );
	}


	public function testPrimaryName()
	{
		$this->tablemock->expects( $this->once() )->method( 'setPrimaryKey' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->primary( 'id', 'pk_id' ) );
	}


	public function testPrimaryExists()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['spansColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'spansColumns' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'getPrimaryKey' )->willReturn( $idxmock );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->primary( 'id', 'pk_id' ) );
	}


	public function testPrimaryChange()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['spansColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'spansColumns' )->willReturn( false );
		$this->tablemock->expects( $this->once() )->method( 'getPrimaryKey' )->willReturn( $idxmock );
		$this->tablemock->expects( $this->once() )->method( 'dropPrimaryKey' );
		$this->tablemock->expects( $this->once() )->method( 'setPrimaryKey' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->primary( 'id', 'pk_id' ) );
	}


	public function testRenameIndex()
	{
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->renameIndex( 'idx_test' ) );
	}


	public function testRenameIndexExists()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'renameIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->renameIndex( 'idx_t1', 'idx_t2' ) );
	}


	public function testRenameIndexMultiple()
	{
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->exactly( 2 ) )->method( 'renameIndex' );

		$idx = ['idx_t1' => 'idx_t2', 'idx_t3' => 'idx_t4'];
		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->renameIndex( $idx ) );
	}


	public function testRenameIndexName()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['getColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'getColumns' )->willReturn( ['a', 'b'] );
		$this->tablemock->expects( $this->once() )->method( 'getIndex' )->willReturn( $idxmock );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );
		$this->tablemock->expects( $this->once() )->method( 'renameIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->renameIndex( 'idx_test' ) );
	}


	public function testRenameColumn()
	{
		$this->tablemock->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );
		$this->dbmock->expects( $this->once() )->method( 'renameColumn' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->renameColumn( 'test' ) );
	}


	public function testSpatial()
	{
		$this->tablemock->expects( $this->once() )->method( 'addIndex' );
		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->spatial( 'pid' ) );
	}


	public function testSpatialName()
	{
		$this->tablemock->expects( $this->once() )->method( 'addIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->spatial( 'pid', 'idx_pid' ) );
	}


	public function testSpatialExists()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['hasFlag', 'spansColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'hasFlag' )->willReturn( true );
		$idxmock->expects( $this->once() )->method( 'spansColumns' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'getIndex' )->willReturn( $idxmock );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->spatial( 'pid', 'idx_pid' ) );
	}


	public function testSpatialChange()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['hasFlag', 'spansColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'hasFlag' )->willReturn( true );
		$idxmock->expects( $this->once() )->method( 'spansColumns' )->willReturn( false );
		$this->tablemock->expects( $this->once() )->method( 'getIndex' )->willReturn( $idxmock );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'dropIndex' );
		$this->tablemock->expects( $this->once() )->method( 'addIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->spatial( 'pid', 'idx_pid' ) );
	}


	public function testUnique()
	{
		$this->tablemock->expects( $this->once() )->method( 'addUniqueIndex' );
		$this->tablemock->expects( $this->once() )->method( 'getName' )->willReturn( 'test' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->unique( 'pid' ) );
	}


	public function testUniqueName()
	{
		$this->tablemock->expects( $this->once() )->method( 'addUniqueIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->unique( 'pid', 'unq_pid' ) );
	}


	public function testUniqueExists()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['isUnique', 'spansColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'isUnique' )->willReturn( true );
		$idxmock->expects( $this->once() )->method( 'spansColumns' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'getIndex' )->willReturn( $idxmock );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->unique( 'pid', 'unq_pid' ) );
	}


	public function testUniqueChange()
	{
		$idxmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Index' )
			->onlyMethods( ['isUnique', 'spansColumns'] )
			->disableOriginalConstructor()
			->getMock();

		$idxmock->expects( $this->once() )->method( 'isUnique' )->willReturn( true );
		$idxmock->expects( $this->once() )->method( 'spansColumns' )->willReturn( false );
		$this->tablemock->expects( $this->once() )->method( 'getIndex' )->willReturn( $idxmock );
		$this->tablemock->expects( $this->once() )->method( 'hasIndex' )->willReturn( true );
		$this->tablemock->expects( $this->once() )->method( 'dropIndex' );
		$this->tablemock->expects( $this->once() )->method( 'addUniqueIndex' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->unique( 'pid', 'unq_pid' ) );
	}


	public function testUp()
	{
		$this->dbmock->expects( $this->once() )->method( 'up' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Table::class, $this->object->up() );
	}


	public function testCopyColumn()
	{
		$dbalcol = new \Doctrine\DBAL\Schema\Column( 'unittest2', \Doctrine\DBAL\Types\Type::getType( 'string' ) );

		$dbaltable = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->disableOriginalConstructor()
			->onlyMethods( ['addColumn'] )
			->getMock();

		$dbaltable->expects( $this->once() )->method( 'addColumn' );

		$object = new \Aimeos\Upscheme\Schema\Table( $this->dbmock, $dbaltable );

		$this->access( 'copyColumn' )->invokeArgs( $object, [$dbalcol, 'unittest'] );
	}


	public function testCopyColumnExisting()
	{
		$dbalcol = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Column' )
			->disableOriginalConstructor()
			->onlyMethods( ['toArray'] )
			->getMock();

		$dbaltable = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Table' )
			->onlyMethods( ['modifyColumn', 'hasColumn'] )
			->disableOriginalConstructor()
			->getMock();

			$dbalcol->expects( $this->once() )->method( 'toArray' )->willReturn( [] );
		$dbaltable->expects( $this->once() )->method( 'hasColumn' )->willReturn( true );
		$dbaltable->expects( $this->once() )->method( 'modifyColumn' );

		$object = new \Aimeos\Upscheme\Schema\Table( $this->dbmock, $dbaltable );

		$this->access( 'copyColumn' )->invokeArgs( $object, [$dbalcol, 'unittest'] );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Upscheme\Schema\Table::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
