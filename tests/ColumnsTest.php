<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm;

use Charcoal\Buffers\AbstractFixedLenBuffer;
use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\Frames\Bytes20;
use Charcoal\Buffers\Frames\Bytes32P;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Schema\Columns\BinaryColumn;
use Charcoal\Database\Orm\Schema\Columns\BoolColumn;
use Charcoal\Database\Orm\Schema\Columns\FrameColumn;
use Charcoal\Database\Orm\Schema\Columns\IntegerColumn;
use Charcoal\Database\Orm\Schema\Columns\StringColumn;
use Charcoal\Database\Orm\Schema\Columns\EnumObjectColumn;
use Charcoal\Database\Tests\Orm\Models\TestEnum;
use Charcoal\Database\Tests\Orm\Models\UserRole;

/**
 * Class ColumnsTest
 */
class ColumnsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testPropertyNames(): void
    {
        $col1 = new IntegerColumn("test");
        $col2 = new StringColumn("test_column_name");
        $col3 = new BinaryColumn("testColumn");
        $col4 = new BinaryColumn("TestColumn");

        $this->assertEquals("test", $col1->attributes->modelMapKey);
        $this->assertEquals("testColumnName", $col2->attributes->modelMapKey, "from snake case");
        $this->assertEquals("testColumn", $col3->attributes->modelMapKey);
        $this->assertEquals("TestColumn", $col4->attributes->modelMapKey);
    }

    /**
     * @return void
     */
    public function testIntegerColumnMySql(): void
    {
        $col = new IntegerColumn("test_column");
        $col->bytes(1);
        $this->assertEquals("tinyint", $col->getColumnSQL(DbDriver::MYSQL));
        $col->bytes(2);
        $this->assertEquals("smallint", $col->getColumnSQL(DbDriver::MYSQL));
        $col->bytes(4);
        $this->assertEquals("int", $col->getColumnSQL(DbDriver::MYSQL));
        $col->bytes(8);
        $this->assertEquals("bigint", $col->getColumnSQL(DbDriver::MYSQL));
    }

    /**
     * @return void
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     */
    public function testBinaryColumn(): void
    {
        $columns = new \Charcoal\Database\Orm\Schema\Columns();
        $col1 = $columns->binary("bin1", plainString: true);
        $col2 = $columns->binary("bin2", plainString: false);

        $this->assertEquals("BLOB", $col1->getColumnSQL(DbDriver::SQLITE));
        $this->assertEquals("varbinary(255)", $col1->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("varbinary(255)", $col2->getColumnSQL(DbDriver::MYSQL));

        $this->assertNotInstanceOf(FrameColumn::class, $col1);
        $this->assertInstanceOf(FrameColumn::class, $col2);

        $binary = "\tcharcoal\r\n";
        $value1 = $col1->attributes->resolveForModelProperty($binary);
        $this->assertIsString($value1);
        $this->assertEquals(strlen($binary), strlen($value1));
        $value2 = $col2->attributes->resolveForModelProperty($binary);
        $this->assertInstanceOf(Buffer::class, $value2);
        $this->assertEquals(strlen($binary), $value2->len());
        $this->assertEquals($binary, $value2->raw());

        $back1 = $col1->attributes->resolveValueForDb($value1, $col1);
        $this->assertEquals($binary, $back1);
        $back2 = $col2->attributes->resolveValueForDb($value2, $col2);
        $this->assertEquals($binary, $back2);
    }

    /**
     * @return void
     */
    public function testBinaryColumnFrames(): void
    {
        $columns = new \Charcoal\Database\Orm\Schema\Columns();
        $col1 = $columns->binary("bin1", plainString: false)->fixed(20); // This is a frame
        $col2 = $columns->binary("bin2", plainString: false)->length(20); // This is not fixed-length frame
        $col3 = $columns->binaryFrame("bin3")->fixed(32);

        $this->assertEquals("binary(20)", $col1->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("varbinary(20)", $col2->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("binary(32)", $col3->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("BLOB", $col1->getColumnSQL(DbDriver::SQLITE));

        $value1 = $col1->attributes->resolveForModelProperty("charcoal");
        $this->assertInstanceOf(Bytes20::class, $value1);
        $this->assertEquals(20, $value1->len());
        $this->assertEquals("\0\0\0\0\0\0\0\0\0\0\0\0charcoal", $value1->raw());

        $value2 = $col2->attributes->resolveForModelProperty("");
        $this->assertInstanceOf(Buffer::class, $value2, "Is Buffer, not Bytes20");
        $this->assertNotInstanceOf(AbstractFixedLenBuffer::class, $col2, "Not a frame because its var-length");

        $value3 = $col3->attributes->resolveForModelProperty("");
        $this->assertInstanceOf(Bytes32P::class, $value3);

        $null1 = $col1->attributes->resolveForModelProperty(null);
        $this->assertNull($null1);
    }

    /**
     * @return void
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     */
    public function testEnum(): void
    {
        $columns = new \Charcoal\Database\Orm\Schema\Columns();
        $enumStr = $columns->enum("role1", enumClass: null)->options("user", "mod");
        $enumClass = $columns->enum("role2", enumClass: UserRole::class)
            ->options("user", "mod")->default("user");
        $thirdEnum = $columns->enum("something", enumClass: TestEnum::class)
            ->options("case_a1", "case_b2", "case_c3");

        $value1 = $enumStr->attributes->resolveForModelProperty("mod");
        $this->assertIsString($value1);
        $this->assertEquals("mod", $value1);

        $value2a = $enumClass->attributes->resolveForModelProperty("user");
        $value2b = $enumClass->attributes->resolveForModelProperty("mod");
        $this->assertInstanceOf(UserRole::class, $value2a);
        $this->assertEquals("user", $value2a->value);
        $this->assertInstanceOf(UserRole::class, $value2b);
        $this->assertEquals("mod", $value2b->value);

        $back1 = $enumStr->attributes->resolveValueForDb($value1, $enumStr);
        $this->assertEquals("mod", $back1);
        $back2a = $enumClass->attributes->resolveValueForDb($value2a, $enumClass);
        $back2b = $enumClass->attributes->resolveValueForDb($value2b, $enumClass);
        $this->assertEquals("user", $back2a);
        $this->assertEquals("mod", $back2b);

        $value3 = $thirdEnum->attributes->resolveForModelProperty("case_b2");
        $this->assertInstanceOf(TestEnum::class, $value3);
        $this->assertEquals(TestEnum::CASE2, $value3);
    }

    /**
     * @return void
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     */
    public function testBool(): void
    {
        $col1 = (new BoolColumn("status1"))->default(false);

        $this->assertEquals("tinyint", $col1->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("integer", $col1->getColumnSQL(DbDriver::SQLITE));
        $this->assertFalse($col1->attributes->resolveForModelProperty("test"));
        $this->assertFalse($col1->attributes->resolveForModelProperty("1"));
        $this->assertFalse($col1->attributes->resolveForModelProperty("0"));
        $this->assertFalse($col1->attributes->resolveForModelProperty(0));
        $this->assertTrue($col1->attributes->resolveForModelProperty(1));
        $this->assertFalse($col1->attributes->resolveForModelProperty(12314));

        $this->assertEquals(0, $col1->attributes->resolveValueForDb(null));
        $this->assertEquals(0, $col1->attributes->resolveValueForDb(false));
        $this->assertEquals(0, $col1->attributes->resolveValueForDb("charcoal"));
        $this->assertEquals(0, $col1->attributes->resolveValueForDb(1));
        $this->assertEquals(1, $col1->attributes->resolveValueForDb(true));
        $this->assertEquals(0, $col1->attributes->resolveValueForDb(1312));
    }

    /**
     * @return void
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     */
    public function testSerializeColumn(): void
    {
        $cols[] = (new IntegerColumn("id"))->bytes(2)->unSigned();
        $cols[] = (new FrameColumn("frame1"))->fixed(20);
        $cols[] = (new EnumObjectColumn("opt1", UserRole::class))
            ->options("user", "mod");
        $cols[] = (new EnumObjectColumn("opt2", TestEnum::class))
            ->options("case_a1", "case_b2", "case_c3");

        $serialized = serialize($cols);
        unset($cols);

        $columns = unserialize($serialized);
        /** @var IntegerColumn $intCol */
        $intCol = $columns[0];
        /** @var FrameColumn $frameCol */
        $frameCol = $columns[1];
        /** @var EnumObjectColumn $opts1Col */
        $opts1Col = $columns[2];
        /** @var EnumObjectColumn $opts2Col */
        $opts2Col = $columns[3];

        $value1 = $intCol->attributes->resolveForModelProperty(0xfe);
        $this->assertEquals(254, $intCol->attributes->resolveValueForDb($value1, $intCol));
        unset($value1a);

        $value2 = $frameCol->attributes->resolveForModelProperty("charcoal");
        $this->assertInstanceOf(Bytes20::class, $value2);
        $this->assertEquals("\0\0\0\0\0\0\0\0\0\0\0\0charcoal", $value2->raw());
        $this->assertEquals("charcoal", trim($frameCol->attributes->resolveValueForDb($value2, $frameCol)));
        unset($value2);

        $value3 = $opts1Col->attributes->resolveForModelProperty("user");
        $this->assertInstanceOf(UserRole::class, $value3);
        $this->assertEquals(UserRole::USER, $value3);
        $this->assertEquals("user", $opts1Col->attributes->resolveValueForDb($value3, $opts1Col));
        unset($value3);

        $value4 = $opts2Col->attributes->resolveForModelProperty("case_c3");
        $this->assertNotInstanceOf(UserRole::class, $value4);
        $this->assertInstanceOf(TestEnum::class, $value4);
        $this->assertEquals(TestEnum::CASE3, $value4);
        $this->assertEquals(TestEnum::CASE3->value, $opts2Col->attributes->resolveValueForDb($value4, $opts2Col));
    }
}
