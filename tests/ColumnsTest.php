<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm;

use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\Types\Bytes20;
use Charcoal\Buffers\Types\Bytes32;
use Charcoal\Contracts\Buffers\WritableBufferInterface;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Schema\Builder\Columns\AbstractColumnBuilder;
use Charcoal\Database\Orm\Schema\Builder\Columns\BinaryColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\BoolColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\EnumObjectColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\FrameColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\IntegerColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\StringColumn;
use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;
use Charcoal\Database\Tests\Orm\Models\TestEnum;
use Charcoal\Database\Tests\Orm\Models\UserRole;

/**
 * Class ColumnsTest
 */
class ColumnsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Pipe a value from the database to the entity object.
     */
    private function pipeValueForEntity(ColumnSnapshot $snap, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $snap->valuePipe?->forEntity($value, $snap) ?? $value;
    }

    /**
     * Pipe a value from the entity object to the database.
     */
    private function pipeValueForDb(ColumnSnapshot $snap, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $snap->valuePipe?->forDb($value, $snap) ?? $value;
    }

    /**
     * @return void
     */
    public function testPropertyNames(): void
    {
        $col1 = new IntegerColumn("test");
        $col2 = new StringColumn("test_column_name");
        $col3 = new BinaryColumn("testColumn");
        $col4 = new BinaryColumn("TestColumn");

        $this->assertEquals("test", $col1->getAttributes()->entityMapKey);
        $this->assertEquals("testColumnName", $col2->getAttributes()->entityMapKey, "from snake case");
        $this->assertEquals("testColumn", $col3->getAttributes()->entityMapKey);
        $this->assertEquals("TestColumn", $col4->getAttributes()->entityMapKey);
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
     */
    public function testBinaryColumn(): void
    {
        $columns = new \Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder();
        $col1 = $columns->binary("bin1", plainString: true);
        $col2 = $columns->binary("bin2", plainString: false);

        $this->assertEquals("BLOB", $col1->getColumnSQL(DbDriver::SQLITE));
        $this->assertEquals("varbinary(255)", $col1->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("varbinary(255)", $col2->getColumnSQL(DbDriver::MYSQL));

        $this->assertNotInstanceOf(FrameColumn::class, $col1);
        $this->assertInstanceOf(FrameColumn::class, $col2);

        $snap1 = $col1->snapshot($col1->getColumnSQL(DbDriver::MYSQL));
        $snap2 = $col2->snapshot($col2->getColumnSQL(DbDriver::MYSQL));

        $binary = "\tcharcoal\r\n";
        $value1 = $snap1->valuePipe?->forEntity($binary, $snap1) ?? $binary;
        $this->assertIsString($value1);
        $this->assertEquals(strlen($binary), strlen($value1));
        $value2 = $snap2->valuePipe?->forEntity($binary, $snap2) ?? $binary;
        $this->assertInstanceOf(Buffer::class, $value2);
        $this->assertEquals(strlen($binary), $value2->length());
        $this->assertEquals($binary, $value2->bytes());

        $back1 = $snap1->valuePipe?->forDb($value1, $snap1) ?? $value1;
        $this->assertEquals($binary, $back1);
        $back2 = $snap2->valuePipe?->forDb($value2, $snap2) ?? $value2;
        $this->assertEquals($binary, $back2);
    }

    /**
     * @return void
     */
    public function testBinaryColumnFrames(): void
    {
        $columns = new \Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder();
        $col1 = $columns->binary("bin1", plainString: false)->fixed(20); // This is a frame
        $col2 = $columns->binary("bin2", plainString: false)->length(20); // This is not fixed-length frame
        $col3 = $columns->binaryFrame("bin3")->fixed(32);

        $snap1 = $col1->snapshot($col1->getColumnSQL(DbDriver::MYSQL));
        $snap2 = $col2->snapshot($col2->getColumnSQL(DbDriver::MYSQL));
        $snap3 = $col3->snapshot($col3->getColumnSQL(DbDriver::MYSQL));

        $this->assertEquals("binary(20)", $col1->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("varbinary(20)", $col2->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("binary(32)", $col3->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("BLOB", $col1->getColumnSQL(DbDriver::SQLITE));

        $value1 = $this->pipeValueForEntity($snap1, "charcoal");
        $this->assertInstanceOf(Bytes20::class, $value1);
        $this->assertEquals(20, $value1->length());
        $this->assertEquals("\0\0\0\0\0\0\0\0\0\0\0\0charcoal", $value1->bytes());

        $value2 = $this->pipeValueForEntity($snap2, "");
        $this->assertInstanceOf(Buffer::class, $value2, "Is Buffer, not Bytes20");
        $this->assertNotInstanceOf(WritableBufferInterface::class, $col2, "Not a frame because its var-length");

        $value3 = $this->pipeValueForEntity($snap3, "");
        $this->assertInstanceOf(Bytes32::class, $value3);
        $this->assertTrue($value3->isPadded());
        $this->assertTrue($value3->isEmpty()); // All null bytes

        $null1 = $this->pipeValueForEntity($snap1, null);
        $this->assertNull($null1);
    }

    /**
     * @return void
     */
    public function testEnum(): void
    {
        $columns = new \Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder();
        $enumStr = $columns->enum("role1", enumClass: null)->options("user", "mod");
        $enumClass = $columns->enum("role2", enumClass: UserRole::class)
            ->options("user", "mod")->default("user");
        $thirdEnum = $columns->enum("something", enumClass: TestEnum::class)
            ->options("case_a1", "case_b2", "case_c3");

        $enumStr = $enumStr->snapshot($enumStr->getColumnSQL(DbDriver::MYSQL));
        $enumClass = $enumClass->snapshot($enumClass->getColumnSQL(DbDriver::MYSQL));
        $thirdEnum = $thirdEnum->snapshot($thirdEnum->getColumnSQL(DbDriver::MYSQL));

        $value1 = $this->pipeValueForEntity($enumStr, "mod");
        $this->assertIsString($value1);
        $this->assertEquals("mod", $value1);

        $value2a = $this->pipeValueForEntity($enumClass, "user");
        $value2b = $this->pipeValueForEntity($enumClass, "mod");
        $this->assertInstanceOf(UserRole::class, $value2a);
        $this->assertEquals("user", $value2a->value);
        $this->assertInstanceOf(UserRole::class, $value2b);
        $this->assertEquals("mod", $value2b->value);

        $back1 = $this->pipeValueForDb($enumStr, $value1);
        $this->assertEquals("mod", $back1);
        $back2a = $this->pipeValueForDb($enumClass, $value2a);
        $back2b = $this->pipeValueForDb($enumClass, $value2b);
        $this->assertEquals("user", $back2a);
        $this->assertEquals("mod", $back2b);

        $value3 = $this->pipeValueForEntity($thirdEnum, "case_b2");
        $this->assertInstanceOf(TestEnum::class, $value3);
        $this->assertEquals(TestEnum::CASE2, $value3);
    }

    /**
     * @return void
     */
    public function testBool(): void
    {
        $col1 = (new BoolColumn("status1"))->default(false);

        $this->assertEquals("tinyint", $col1->getColumnSQL(DbDriver::MYSQL));
        $this->assertEquals("integer", $col1->getColumnSQL(DbDriver::SQLITE));
        $snap1 = $col1->snapshot($col1->getColumnSQL(DbDriver::MYSQL));
        $this->assertFalse($this->pipeValueForEntity($snap1, "test"));
        $this->assertFalse($this->pipeValueForEntity($snap1, "1"));
        $this->assertFalse($this->pipeValueForEntity($snap1, "0"));
        $this->assertFalse($this->pipeValueForEntity($snap1, 0));
        $this->assertTrue($this->pipeValueForEntity($snap1, 1));
        $this->assertFalse($this->pipeValueForEntity($snap1, 12314));

        $this->assertEquals(0, $this->pipeValueForDb($snap1, null));
        $this->assertEquals(0, $this->pipeValueForDb($snap1, false));

        try {
            $this->assertEquals(0, $this->pipeValueForDb($snap1, "charcoal"));
            throw new \AssertionError("Should not have passed");
        } catch (\AssertionError) {
        }

        try {
            $this->assertEquals(0, $this->pipeValueForDb($snap1, 1));
            throw new \AssertionError("Should not have passed");
        } catch (\AssertionError) {
        }

        $this->assertEquals(1, $this->pipeValueForDb($snap1, true));

        try {
            $this->assertEquals(0, $this->pipeValueForDb($snap1, 1312));
            throw new \AssertionError("Should not have passed");
        } catch (\AssertionError) {
        }
    }

    /**
     * @return void
     */
    public function testSerializeColumn(): void
    {
        $cols[] = (new IntegerColumn("id"))->bytes(2)->unSigned();
        $cols[] = (new FrameColumn("frame1"))->fixed(20);
        $cols[] = (new EnumObjectColumn("opt1", UserRole::class))
            ->options("user", "mod");
        $cols[] = (new EnumObjectColumn("opt2", TestEnum::class))
            ->options("case_a1", "case_b2", "case_c3");

        $cols = array_map(fn(AbstractColumnBuilder $cB) => $cB->snapshot($cB->getColumnSQL(DbDriver::MYSQL)),
            $cols);
        $serialized = serialize($cols);
        unset($cols);

        $columns = unserialize($serialized);
        /** @var ColumnSnapshot $intCol */
        $intCol = $columns[0];
        /** @var ColumnSnapshot $frameCol */
        $frameCol = $columns[1];
        /** @var ColumnSnapshot $opts1Col */
        $opts1Col = $columns[2];
        /** @var ColumnSnapshot $opts2Col */
        $opts2Col = $columns[3];

        $value1 = $this->pipeValueForEntity($intCol, 0xfe);
        $this->assertEquals(254, $this->pipeValueForDb($intCol, $value1));
        unset($value1a);

        $value2 = $this->pipeValueForEntity($frameCol, "charcoal");
        $this->assertInstanceOf(Bytes20::class, $value2);
        $this->assertEquals("\0\0\0\0\0\0\0\0\0\0\0\0charcoal", $value2->bytes());
        $this->assertEquals("charcoal", trim($this->pipeValueForDb($frameCol, $value2)));
        unset($value2);

        $value3 = $this->pipeValueForEntity($opts1Col, "user");
        $this->assertInstanceOf(UserRole::class, $value3);
        $this->assertEquals(UserRole::USER, $value3);
        $this->assertEquals("user", $this->pipeValueForDb($opts1Col, $value3));
        unset($value3);

        $value4 = $this->pipeValueForEntity($opts2Col, "case_c3");
        $this->assertNotInstanceOf(UserRole::class, $value4);
        $this->assertInstanceOf(TestEnum::class, $value4);
        $this->assertEquals(TestEnum::CASE3, $value4);
        $this->assertEquals(TestEnum::CASE3->value, $this->pipeValueForDb($opts2Col, $value4));
    }
}
