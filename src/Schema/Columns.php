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

namespace Charcoal\Database\ORM\Schema;

use Charcoal\Database\ORM\Schema\Columns\AbstractColumn;
use Charcoal\Database\ORM\Schema\Columns\BinaryColumn;
use Charcoal\Database\ORM\Schema\Columns\BlobColumn;
use Charcoal\Database\ORM\Schema\Columns\BoolColumn;
use Charcoal\Database\ORM\Schema\Columns\BufferColumn;
use Charcoal\Database\ORM\Schema\Columns\DecimalColumn;
use Charcoal\Database\ORM\Schema\Columns\DoubleColumn;
use Charcoal\Database\ORM\Schema\Columns\EnumColumn;
use Charcoal\Database\ORM\Schema\Columns\EnumObjectColumn;
use Charcoal\Database\ORM\Schema\Columns\FloatColumn;
use Charcoal\Database\ORM\Schema\Columns\FrameColumn;
use Charcoal\Database\ORM\Schema\Columns\IntegerColumn;
use Charcoal\Database\ORM\Schema\Columns\StringColumn;
use Charcoal\Database\ORM\Schema\Columns\TextColumn;

/**
 * Class Columns
 * @package Charcoal\Database\ORM\Schema
 */
class Columns implements \IteratorAggregate
{
    /** @var array */
    private array $columns = [];
    /** @var int */
    private int $count = 0;
    /** @var \Charcoal\Database\ORM\Schema\Charset */
    private Charset $defaultCharset = Charset::ASCII;
    /** @var null|string */
    private ?string $primaryKey = null;

    /**
     * @return array
     */
    public function names(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @param \Charcoal\Database\ORM\Schema\Charset|null $charset
     * @return $this
     */
    public function setDefaultCharset(Charset $charset = null): static
    {
        $this->defaultCharset = $charset;
        return $this;
    }

    /**
     * @param \Charcoal\Database\ORM\Schema\Columns\AbstractColumn $column
     * @return void
     */
    public function append(AbstractColumn $column): void
    {
        $this->columns[$column->attributes->name] = $column;
        $this->count++;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\AbstractColumn
     */
    public function get(string $name): AbstractColumn
    {
        if (!isset($this->columns[$name])) {
            throw new \OutOfBoundsException(sprintf('No definition exists for column `%s`', $name));
        }

        return $this->columns[$name];
    }

    /**
     * @param string $key
     * @return \Charcoal\Database\ORM\Schema\Columns\AbstractColumn|null
     */
    public function search(string $key): ?AbstractColumn
    {
        if (isset($this->columns[$key])) {
            return $this->columns[$key];
        }

        /** @var AbstractColumn $column */
        foreach ($this->columns as $column) {
            if ($key === $column->attributes->modelProperty) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\IntegerColumn
     */
    public function int(string $name): IntegerColumn
    {
        $col = new IntegerColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\StringColumn
     */
    public function string(string $name): StringColumn
    {
        $col = new StringColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    /**
     * @param string $name
     * @param bool $plainString
     * @return \Charcoal\Database\ORM\Schema\Columns\BinaryColumn
     */
    public function binary(string $name, bool $plainString = true): BinaryColumn
    {
        if (!$plainString) {
            return $this->binaryFrame($name);
        }

        $col = new BinaryColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\FrameColumn
     */
    public function binaryFrame(string $name): FrameColumn
    {
        $col = new FrameColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\TextColumn
     */
    public function text(string $name): TextColumn
    {
        $col = new TextColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    /**
     * @param string $name
     * @param bool $plainString
     * @return \Charcoal\Database\ORM\Schema\Columns\BlobColumn
     */
    public function blob(string $name, bool $plainString = true): BlobColumn
    {
        if (!$plainString) {
            return $this->blobBuffer($name);
        }

        $col = new BlobColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\BufferColumn
     */
    public function blobBuffer(string $name): BufferColumn
    {
        $col = new BufferColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\DecimalColumn
     */
    public function decimal(string $name): DecimalColumn
    {
        $col = new DecimalColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\FloatColumn
     */
    public function float(string $name): FloatColumn
    {
        $col = new FloatColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\DoubleColumn
     */
    public function double(string $name): DoubleColumn
    {
        $col = new DoubleColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $name
     * @param string|null $enumClass
     * @return \Charcoal\Database\ORM\Schema\Columns\EnumColumn
     */
    public function enum(string $name, ?string $enumClass = null): EnumColumn
    {
        if ($enumClass) {
            return $this->enumObject($name, $enumClass);
        }

        $col = new EnumColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    /**
     * @param string $name
     * @param string $enumClass
     * @return \Charcoal\Database\ORM\Schema\Columns\EnumObjectColumn
     */
    public function enumObject(string $name, string $enumClass): EnumObjectColumn
    {
        $col = new EnumObjectColumn($name, $enumClass);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    /**
     * @param string $name
     * @return \Charcoal\Database\ORM\Schema\Columns\BoolColumn
     */
    public function bool(string $name): BoolColumn
    {
        $col = new BoolColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * @param string $col
     * @param bool $defaultValueCheck
     * @return void
     */
    public function setPrimaryKey(string $col, bool $defaultValueCheck = true): void
    {
        /** @var AbstractColumn $column */
        $column = $this->columns[$col] ?? null;
        if (!$column) {
            throw new \InvalidArgumentException(sprintf('Column "%s" not defined in table', $col));
        }

        if ($column->attributes->nullable) {
            throw new \InvalidArgumentException(sprintf('Primary key "%s" cannot be nullable', $col));
        }

        if ($defaultValueCheck && is_null($column->attributes->defaultValue)) {
            if (!$column instanceof IntegerColumn || !$column->attributes->autoIncrement) {
                throw new \InvalidArgumentException(sprintf('Primary key "%s" default value cannot be NULL', $col));
            }
        }

        $this->primaryKey = $col;
    }

    /**
     * @return string|null
     */
    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->columns);
    }
}
