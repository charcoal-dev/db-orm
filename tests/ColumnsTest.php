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
        $this->assertInstanceOf(\Charcoal\Buffers\Buffer::class, $value2, "Is Buffer not Bytes20");
        $this->assertNotInstanceOf(\Charcoal\Buffers\AbstractFixedLenBuffer::class, $col2, "Not a frame because its var-length");

        $value3 = $col3->attributes->getResolvedModelProperty("");
        $this->assertInstanceOf(\Charcoal\Buffers\Frames\Bytes32P::class, $value3);

        $null1 = $col1->attributes->getResolvedModelProperty(null);
        $this->assertNull($null1);
    }
}
