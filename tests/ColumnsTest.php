<?php
/*
 * This file is a part of "charcoal-dev/db-orm" package.
 * https://github.com/charcoal-dev/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/db-orm/blob/main/LICENSE
 */

declare(strict_types=1);

require_once "TestModels.php";

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
        $col1 = new \Charcoal\Database\ORM\Schema\Columns\IntegerColumn("test");
        $col2 = new \Charcoal\Database\ORM\Schema\Columns\StringColumn("test_column_name");
        $col3 = new \Charcoal\Database\ORM\Schema\Columns\BinaryColumn("testColumn");
        $col4 = new \Charcoal\Database\ORM\Schema\Columns\BinaryColumn("TestColumn");

        $this->assertEquals("test", $col1->attributes->modelProperty);
        $this->assertEquals("testColumnName", $col2->attributes->modelProperty, "from snake case");
        $this->assertEquals("testColumn", $col3->attributes->modelProperty);
        $this->assertEquals("TestColumn", $col4->attributes->modelProperty);
    }

    /**
     * @return void
     */
    public function testIntegerColumnMySql(): void
    {
        $col = new \Charcoal\Database\ORM\Schema\Columns\IntegerColumn("test_column");
        $col->bytes(1);
        $this->assertEquals("tinyint", $col->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));
        $col->bytes(2);
        $this->assertEquals("smallint", $col->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));
        $col->bytes(4);
        $this->assertEquals("int", $col->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));
        $col->bytes(8);
        $this->assertEquals("bigint", $col->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));
    }

    /**
     * @return void
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function testBinaryColumn(): void
    {
        $columns = new \Charcoal\Database\ORM\Schema\Columns();
        $col1 = $columns->binary("bin1", plainString: true);
        $col2 = $columns->binary("bin2", plainString: false);

        $this->assertEquals("BLOB", $col1->getColumnSQL(\Charcoal\Database\DbDriver::SQLITE));
        $this->assertEquals("varbinary(255)", $col1->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));
        $this->assertEquals("varbinary(255)", $col2->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));

        $this->assertNotInstanceOf(\Charcoal\Database\ORM\Schema\Columns\FrameColumn::class, $col1);
        $this->assertInstanceOf(\Charcoal\Database\ORM\Schema\Columns\FrameColumn::class, $col2);

        $binary = "\tcharcoal\r\n";
        $value1 = $col1->attributes->getResolvedModelProperty($binary);
        $this->assertIsString($value1);
        $this->assertEquals(strlen($binary), strlen($value1));
        $value2 = $col2->attributes->getResolvedModelProperty($binary);
        $this->assertInstanceOf(\Charcoal\Buffers\Buffer::class, $value2);
        $this->assertEquals(strlen($binary), $value2->len());
        $this->assertEquals($binary, $value2->raw());

        $back1 = $col1->attributes->getDissolvedModelProperty($value1, $col1);
        $this->assertEquals($binary, $back1);
        $back2 = $col2->attributes->getDissolvedModelProperty($value2, $col2);
        $this->assertEquals($binary, $back2);
    }

    /**
     * @return void
     */
    public function testBinaryColumnFrames(): void
    {
        $columns = new \Charcoal\Database\ORM\Schema\Columns();
        $col1 = $columns->binary("bin1", plainString: false)->fixed(20); // This is a frame
        $col2 = $columns->binary("bin2", plainString: false)->length(20); // This is not fixed-length frame
        $col3 = $columns->binaryFrame("bin3")->fixed(32);

        $this->assertEquals("binary(20)", $col1->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));
        $this->assertEquals("varbinary(20)", $col2->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));
        $this->assertEquals("binary(32)", $col3->getColumnSQL(\Charcoal\Database\DbDriver::MYSQL));
        $this->assertEquals("BLOB", $col1->getColumnSQL(\Charcoal\Database\DbDriver::SQLITE));

        $value1 = $col1->attributes->getResolvedModelProperty("charcoal");
        $this->assertInstanceOf(\Charcoal\Buffers\Frames\Bytes20::class, $value1);
        $this->assertEquals(20, $value1->len());
        $this->assertEquals("\0\0\0\0\0\0\0\0\0\0\0\0charcoal", $value1->raw());

        $value2 = $col2->attributes->getResolvedModelProperty("");
        $this->assertInstanceOf(\Charcoal\Buffers\Buffer::class, $value2, "Is Buffer, not Bytes20");
        $this->assertNotInstanceOf(\Charcoal\Buffers\AbstractFixedLenBuffer::class, $col2, "Not a frame because its var-length");

        $value3 = $col3->attributes->getResolvedModelProperty("");
        $this->assertInstanceOf(\Charcoal\Buffers\Frames\Bytes32P::class, $value3);

        $null1 = $col1->attributes->getResolvedModelProperty(null);
        $this->assertNull($null1);
    }

    /**
     * @return void
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function testEnum(): void
    {
        $columns = new \Charcoal\Database\ORM\Schema\Columns();
        $enumStr = $columns->enum("role1", enumClass: null)->options("user", "mod");
        $enumClass = $columns->enum("role2", enumClass: \Charcoal\Tests\ORM\UserRole::class)
            ->options("user", "mod")->default("user");
        $thirdEnum = $columns->enum("something", enumClass: \Charcoal\Tests\ORM\TestEnum::class)
            ->options("case_a1", "case_b2", "case_c3");

        $value1 = $enumStr->attributes->getResolvedModelProperty("mod");
        $this->assertIsString($value1);
        $this->assertEquals("mod", $value1);

        $value2a = $enumClass->attributes->getResolvedModelProperty("user");
        $value2b = $enumClass->attributes->getResolvedModelProperty("mod");
        $this->assertInstanceOf(\Charcoal\Tests\ORM\UserRole::class, $value2a);
        $this->assertEquals("user", $value2a->value);
        $this->assertInstanceOf(\Charcoal\Tests\ORM\UserRole::class, $value2b);
        $this->assertEquals("mod", $value2b->value);

        $back1 = $enumStr->attributes->getDissolvedModelProperty($value1, $enumStr);
        $this->assertEquals("mod", $back1);
        $back2a = $enumClass->attributes->getDissolvedModelProperty($value2a, $enumClass);
        $back2b = $enumClass->attributes->getDissolvedModelProperty($value2b, $enumClass);
        $this->assertEquals("user", $back2a);
        $this->assertEquals("mod", $back2b);

        $value3 = $thirdEnum->attributes->getResolvedModelProperty("case_b2");
        $this->assertInstanceOf(\Charcoal\Tests\ORM\TestEnum::class, $value3);
        $this->assertEquals(\Charcoal\Tests\ORM\TestEnum::CASE2, $value3);
    }

    /**
     * @return void
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function testSerializeColumn(): void
    {
        $cols[] = (new \Charcoal\Database\ORM\Schema\Columns\IntegerColumn("id"))->bytes(2)->unSigned();
        $cols[] = (new \Charcoal\Database\ORM\Schema\Columns\FrameColumn("frame1"))->fixed(20);
        $cols[] = (new \Charcoal\Database\ORM\Schema\Columns\EnumObjectColumn("opt1", \Charcoal\Tests\ORM\UserRole::class))
            ->options("user", "mod");
        $cols[] = (new \Charcoal\Database\ORM\Schema\Columns\EnumObjectColumn("opt2", \Charcoal\Tests\ORM\TestEnum::class))
            ->options("case_a1", "case_b2", "case_c3");

        $serialized = serialize($cols);
        unset($cols);

        $columns = unserialize($serialized);
        /** @var \Charcoal\Database\ORM\Schema\Columns\IntegerColumn $intCol */
        $intCol = $columns[0];
        /** @var \Charcoal\Database\ORM\Schema\Columns\FrameColumn $frameCol */
        $frameCol = $columns[1];
        /** @var \Charcoal\Database\ORM\Schema\Columns\EnumObjectColumn $opts1Col */
        $opts1Col = $columns[2];
        /** @var \Charcoal\Database\ORM\Schema\Columns\EnumObjectColumn $opts2Col */
        $opts2Col = $columns[3];

        $value1 = $intCol->attributes->getResolvedModelProperty(0xfe);
        $this->assertEquals(254, $intCol->attributes->getDissolvedModelProperty($value1, $intCol));
        unset($value1a);

        $value2 = $frameCol->attributes->getResolvedModelProperty("charcoal");
        $this->assertInstanceOf(\Charcoal\Buffers\Frames\Bytes20::class, $value2);
        $this->assertEquals("\0\0\0\0\0\0\0\0\0\0\0\0charcoal", $value2->raw());
        $this->assertEquals("charcoal", trim($frameCol->attributes->getDissolvedModelProperty($value2, $frameCol)));
        unset($value2);

        $value3 = $opts1Col->attributes->getResolvedModelProperty("user");
        $this->assertInstanceOf(\Charcoal\Tests\ORM\UserRole::class, $value3);
        $this->assertEquals(\Charcoal\Tests\ORM\UserRole::USER, $value3);
        $this->assertEquals("user", $opts1Col->attributes->getDissolvedModelProperty($value3, $opts1Col));
        unset($value3);

        $value4 = $opts2Col->attributes->getResolvedModelProperty("case_c3");
        $this->assertNotInstanceOf(\Charcoal\Tests\ORM\UserRole::class, $value4);
        $this->assertInstanceOf(\Charcoal\Tests\ORM\TestEnum::class, $value4);
        $this->assertEquals(\Charcoal\Tests\ORM\TestEnum::CASE3, $value4);
        $this->assertEquals(\Charcoal\Tests\ORM\TestEnum::CASE3->value, $opts2Col->attributes->getDissolvedModelProperty($value4, $opts2Col));
    }
}
