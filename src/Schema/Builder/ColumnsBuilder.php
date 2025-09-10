<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder;

use Charcoal\Base\Registry\Traits\InstancedObjectsRegistry;
use Charcoal\Base\Registry\Traits\RegistryKeysLowercaseTrimmed;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\Columns\AbstractColumnBuilder;
use Charcoal\Database\Orm\Schema\Builder\Columns\BinaryColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\BlobColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\BoolColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\BufferColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\DateColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\DecimalColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\DsvColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\EnumColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\EnumObjectColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\FloatColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\FrameColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\IntegerColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\StringColumn;
use Charcoal\Database\Orm\Schema\Builder\Columns\TextColumn;

/**
 * Class ColumnsBuilder
 * @package Charcoal\Database\Orm\Schema
 * @use InstancedObjectsRegistry<AbstractColumnBuilder>
 * @property array<string,AbstractColumnBuilder> $instances
 */
class ColumnsBuilder implements \IteratorAggregate
{
    private int $count = 0;
    private Charset $defaultCharset = Charset::ASCII;
    private ?string $primaryKey = null;

    use InstancedObjectsRegistry;
    use RegistryKeysLowercaseTrimmed;

    /**
     * @return array<string>
     */
    public function names(): array
    {
        return array_keys($this->instances);
    }

    /**
     * Set the default charset for all columns.
     */
    public function setDefaultCharset(Charset $charset = null): static
    {
        $this->defaultCharset = $charset;
        return $this;
    }

    /**
     * Append a column definition to the builder.
     */
    public function append(AbstractColumnBuilder $column): void
    {
        $this->instances[$column->name] = $column;
        $this->count++;
    }

    /**
     * Get a column definition by its name.
     */
    public function get(string $name): AbstractColumnBuilder
    {
        if (!isset($this->instances[$name])) {
            throw new \OutOfBoundsException("Undefined column:" . $name);
        }

        return $this->instances[$name];
    }

    /**
     * Get the number of columns in the builder.
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Define a new integer column.
     * @api
     */
    public function int(string $name): IntegerColumn
    {
        $col = new IntegerColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * Define a new string column.
     * @api
     */
    public function string(string $name): StringColumn
    {
        $col = new StringColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    /**
     * Define a new DSV column.
     * @api
     */
    public function dsvString(string $name, string $delimiter = ","): DsvColumn
    {
        $col = new DsvColumn($name);
        $col->delimiter($delimiter);
        $this->append($col);
        return $col;
    }

    /**
     * Define a new date column.
     * @api
     */
    public function date(string $name): DateColumn
    {
        $col = new DateColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * Define a new binary column.
     * @api
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
     * Define a new binary frame column.
     * @api
     */
    public function binaryFrame(string $name): FrameColumn
    {
        $col = new FrameColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * Define a new text column.
     * @api
     */
    public function text(string $name): TextColumn
    {
        $col = new TextColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    /**
     * Define a new blob column.
     * @api
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
     * Define a new blob buffer column.
     * @api
     */
    public function blobBuffer(string $name): BufferColumn
    {
        $col = new BufferColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * Define a new decimal column.
     * @api
     */
    public function decimal(string $name): DecimalColumn
    {
        $col = new DecimalColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * Define a new float column.
     * @api
     */
    public function float(string $name): FloatColumn
    {
        $col = new FloatColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * Define a new enum column.
     * @api
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
     * Define a new enum object column.
     * @api
     */
    public function enumObject(string $name, string $enumClass): EnumObjectColumn
    {
        $col = new EnumObjectColumn($name, $enumClass);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    /**
     * Define a new boolean column.
     * @api
     */
    public function bool(string $name): BoolColumn
    {
        $col = new BoolColumn($name);
        $this->append($col);
        return $col;
    }

    /**
     * Set the primary key for the table.
     */
    public function setPrimaryKey(string $col, bool $defaultValueCheck = true): void
    {
        $column = $this->instances[$col] ?? null;
        if (!$column) {
            throw new \InvalidArgumentException(sprintf('Column "%s" not defined in table', $col));
        }

        $attr = $column->getAttributes();
        if ($attr->nullable) {
            throw new \InvalidArgumentException(sprintf('Primary key "%s" cannot be nullable', $col));
        }

        if ($defaultValueCheck && is_null($attr->defaultValue)) {
            if (!$column instanceof IntegerColumn || !$attr->autoIncrement) {
                throw new \InvalidArgumentException(sprintf('Primary key "%s" default value cannot be NULL', $col));
            }
        }

        $this->primaryKey = $col;
    }

    /**
     * Get the primary key for the table.
     */
    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }

    /**
     * @return \Traversable<string,AbstractColumnBuilder>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->instances);
    }
}
