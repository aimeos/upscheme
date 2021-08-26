<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */

namespace Aimeos\Upscheme\Schema;


class ColumnTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $dbmock;
	private $colmock;
	private $tablemock;


	protected function setUp() : void
	{
		$this->dbmock = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\DB' )
			->disableOriginalConstructor()
			->getMock();

		$this->tablemock = $this->getMockBuilder( '\Aimeos\Upscheme\Schema\Table' )
			->disableOriginalConstructor()
			->getMock();

		$this->colmock = $this->getMockBuilder( '\Doctrine\DBAL\Schema\Column' )
			->disableOriginalConstructor()
			->getMock();

		$this->object = new \Aimeos\Upscheme\Schema\Column( $this->dbmock, $this->tablemock, $this->colmock );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->colmock, $this->tablemock, $this->dbmock );
	}


	public function testCall()
	{
		$this->colmock->expects( $this->once() )->method( 'getComment' );

		$this->object->getComment();
	}


	public function testCallMacro()
	{
		\Aimeos\Upscheme\Schema\Column::macro( 'unittest', function() { return 'yes'; } );

		$this->assertEquals( 'yes', $this->object->unittest() );
	}


	public function testGetMagic()
	{
		$this->colmock->expects( $this->once() )->method( 'getCustomSchemaOption' )
			->will( $this->returnValue( 'yes' ) );

		$this->assertEquals( 'yes', $this->object->unittest );
	}


	public function testSetMagic()
	{
		$this->colmock->expects( $this->once() )->method( 'setCustomSchemaOption' );

		$this->object->unittest = 'yes';
	}


	public function testOptGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getCustomSchemaOption' )
			->will( $this->returnValue( 'yes' ) );

		$this->assertEquals( 'yes', $this->object->opt( 'unittest' ) );
	}


	public function testOptSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setCustomSchemaOption' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->opt( 'unittest', 'yes' ) );
	}


	public function testOptSetType()
	{
		$this->dbmock->expects( $this->once() )->method( 'type' )->will( $this->returnValue( 'mydb' ) );
		$this->colmock->expects( $this->once() )->method( 'setCustomSchemaOption' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->opt( 'unittest', 'yes', 'mydb' ) );
	}


	public function testOptSetTypeNot()
	{
		$this->dbmock->expects( $this->once() )->method( 'type' )->will( $this->returnValue( 'mydb' ) );
		$this->colmock->expects( $this->never() )->method( 'setCustomSchemaOption' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->opt( 'unittest', 'yes', 'yourdb' ) );
	}


	public function testAutoincrementGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getAutoincrement' )
			->will( $this->returnValue( true ) );

		$this->assertEquals( true, $this->object->autoincrement() );
	}


	public function testAutoincrementSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setAutoincrement' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->autoincrement( true ) );
	}


	public function testCharsetGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getCustomSchemaOption' )
			->will( $this->returnValue( 'utf8' ) );

		$this->assertEquals( 'utf8', $this->object->charset() );
	}


	public function testCharsetSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setCustomSchemaOption' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->charset( 'utf8' ) );
	}


	public function testCollationGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getCustomSchemaOption' )
			->will( $this->returnValue( 'binary' ) );

		$this->assertEquals( 'binary', $this->object->collation() );
	}


	public function testCollationSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setCustomSchemaOption' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->collation( 'binary' ) );
	}


	public function testCommentGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getComment' )
			->will( $this->returnValue( 'yes' ) );

		$this->assertEquals( 'yes', $this->object->comment() );
	}


	public function testCommentSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setComment' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->comment( 'yes' ) );
	}


	public function testDefaultGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getDefault' )
			->will( $this->returnValue( 'yes' ) );

		$this->assertEquals( 'yes', $this->object->default() );
	}


	public function testDefaultSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setDefault' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->default( 'yes' ) );
	}


	public function testFixedGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getFixed' )
			->will( $this->returnValue( true ) );

		$this->assertEquals( true, $this->object->fixed() );
	}


	public function testFixedSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setFixed' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->fixed( true ) );
	}


	public function testLengthGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getLength' )
			->will( $this->returnValue( 10 ) );

		$this->assertEquals( 10, $this->object->length() );
	}


	public function testLengthSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setLength' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->length( 10 ) );
	}


	public function testName()
	{
		$this->colmock->expects( $this->once() )->method( 'getName' )
			->will( $this->returnValue( 'unittest' ) );

		$this->assertEquals( 'unittest', $this->object->name() );
	}


	public function testNullGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getNotnull' )
			->will( $this->returnValue( true ) );

		$this->assertEquals( false, $this->object->null() );
	}


	public function testNullSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setNotnull' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->null( true ) );
	}


	public function testPrecisionGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getPrecision' )
			->will( $this->returnValue( 10 ) );

		$this->assertEquals( 10, $this->object->precision() );
	}


	public function testPrecisionSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setPrecision' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->precision( 10 ) );
	}


	public function testScaleGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getScale' )
			->will( $this->returnValue( 10 ) );

		$this->assertEquals( 10, $this->object->scale() );
	}


	public function testScaleSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setScale' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->scale( 10 ) );
	}


	public function testSeqGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getAutoincrement' )
			->will( $this->returnValue( true ) );

		$this->assertEquals( true, $this->object->seq() );
	}


	public function testSeqSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setAutoincrement' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->seq( true ) );
	}


	public function testTypeGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getType' )
			->will( $this->returnValue( new \Doctrine\DBAL\Types\StringType() ) );

		$this->assertEquals( 'string', $this->object->type() );
	}


	public function testTypeSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setType' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->type( 'string' ) );
	}


	public function testUnsignedGet()
	{
		$this->colmock->expects( $this->once() )->method( 'getUnsigned' )
			->will( $this->returnValue( true ) );

		$this->assertEquals( true, $this->object->unsigned() );
	}


	public function testUnsignedSet()
	{
		$this->colmock->expects( $this->once() )->method( 'setUnsigned' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->unsigned( true ) );
	}


	public function testIndex()
	{
		$this->colmock->expects( $this->once() )->method( 'getName' )->will( $this->returnValue( 'unitcol' ) );
		$this->tablemock->expects( $this->once() )->method( 'index' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->index( 'unittest', 'idx_utst' ) );
	}


	public function testPrimary()
	{
		$this->colmock->expects( $this->once() )->method( 'getName' )->will( $this->returnValue( 'unitcol' ) );
		$this->tablemock->expects( $this->once() )->method( 'primary' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->primary( 'unittest', 'pk_utst' ) );
	}


	public function testSpatial()
	{
		$this->colmock->expects( $this->once() )->method( 'getName' )->will( $this->returnValue( 'unitcol' ) );
		$this->tablemock->expects( $this->once() )->method( 'spatial' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->spatial( 'unittest', 'sp_utst' ) );
	}


	public function testUnique()
	{
		$this->colmock->expects( $this->once() )->method( 'getName' )->will( $this->returnValue( 'unitcol' ) );
		$this->tablemock->expects( $this->once() )->method( 'unique' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->unique( 'unittest', 'unq_utst' ) );
	}


	public function testUp()
	{
		$this->tablemock->expects( $this->once() )->method( 'up' );

		$this->assertInstanceOf( \Aimeos\Upscheme\Schema\Column::class, $this->object->up() );
	}
}
