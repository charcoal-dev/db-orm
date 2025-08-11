<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema;

use Charcoal\Base\Concerns\InstancedObjectsRegistry;
use Charcoal\Base\Concerns\RegistryKeysLowercaseTrimmed;
use Charcoal\Base\Enums\Charset;
use Charcoal\Database\Orm\Schema\Columns\AbstractColumn;
use Charcoal\Database\Orm\Schema\Columns\BinaryColumn;
use Charcoal\Database\Orm\Schema\Columns\BlobColumn;
use Charcoal\Database\Orm\Schema\Columns\BoolColumn;
use Charcoal\Database\Orm\Schema\Columns\BufferColumn;
use Charcoal\Database\Orm\Schema\Columns\DateColumn;
use Charcoal\Database\Orm\Schema\Columns\DecimalColumn;
use Charcoal\Database\Orm\Schema\Columns\DoubleColumn;
use Charcoal\Database\Orm\Schema\Columns\DsvColumn;
use Charcoal\Database\Orm\Schema\Columns\EnumColumn;
use Charcoal\Database\Orm\Schema\Columns\EnumObjectColumn;
use Charcoal\Database\Orm\Schema\Columns\FloatColumn;
use Charcoal\Database\Orm\Schema\Columns\FrameColumn;
use Charcoal\Database\Orm\Schema\Columns\IntegerColumn;
use Charcoal\Database\Orm\Schema\Columns\StringColumn;
use Charcoal\Database\Orm\Schema\Columns\TextColumn;

/**
 * Class Columns
 * @package Charcoal\Database\Orm\Schema
 * @property array<string,AbstractColumn> $instances
 */
class Columns implements \IteratorAggregate
{
    private int $count = 0;
    private Charset $defaultCharset = Charset::ASCII;
    private ?string $primaryKey = null;

    use InstancedObjectsRegistry;
    use RegistryKeysLowercaseTrimmed;

    public function names(): array
    {
        return array_keys($this->instances);
    }

    public function setDefaultCharset(Charset $charset = null): static
    {
        $this->defaultCharset = $charset;
        return $this;
    }

    public function append(AbstractColumn $column): void
    {
        $this->instances[$column->attributes->name] = $column;
        $this->count++;
    }

    public function get(string $name): AbstractColumn
    {
        if (!isset($this->instances[$name])) {
            throw new \OutOfBoundsException(sprintf('No definition exists for column `%s`', $name));
        }

        return $this->instances[$name];
    }

    public function search(string $key): ?AbstractColumn
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        foreach ($this->instances as $column) {
            if ($key === $column->attributes->modelProperty) {
                return $column;
            }
        }

        return null;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function int(string $name): IntegerColumn
    {
        $col = new IntegerColumn($name);
        $this->append($col);
        return $col;
    }

    public function string(string $name): StringColumn
    {
        $col = new StringColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    public function dsvString(string $name, string $delimiter = ","): DsvColumn
    {
        $col = new DsvColumn($name);
        $col->delimiter($delimiter);
        $this->append($col);
        return $col;
    }

    public function date(string $name): DateColumn
    {
        $col = new DateColumn($name);
        $this->append($col);
        return $col;
    }

    public function binary(string $name, bool $plainString = true): BinaryColumn
    {
        if (!$plainString) {
            return $this->binaryFrame($name);
        }

        $col = new BinaryColumn($name);
        $this->append($col);
        return $col;
    }

    public function binaryFrame(string $name): FrameColumn
    {
        $col = new FrameColumn($name);
        $this->append($col);
        return $col;
    }

    public function text(string $name): TextColumn
    {
        $col = new TextColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    public function blob(string $name, bool $plainString = true): BlobColumn
    {
        if (!$plainString) {
            return $this->blobBuffer($name);
        }

        $col = new BlobColumn($name);
        $this->append($col);
        return $col;
    }

    public function blobBuffer(string $name): BufferColumn
    {
        $col = new BufferColumn($name);
        $this->append($col);
        return $col;
    }

    public function decimal(string $name): DecimalColumn
    {
        $col = new DecimalColumn($name);
        $this->append($col);
        return $col;
    }

    public function float(string $name): FloatColumn
    {
        $col = new FloatColumn($name);
        $this->append($col);
        return $col;
    }

    public function double(string $name): DoubleColumn
    {
        $col = new DoubleColumn($name);
        $this->append($col);
        return $col;
    }

    public function enum(string $name, ?string $enumClass = null): EnumColumn
    {
        if ($enumClass) {
            return $this->enumObject($name, $enumClass);
        }

        $col = new EnumColumn($name);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    public function enumObject(string $name, string $enumClass): EnumObjectColumn
    {
        $col = new EnumObjectColumn($name, $enumClass);
        $this->append($col);
        return $col->charset($this->defaultCharset);
    }

    public function bool(string $name): BoolColumn
    {
        $col = new BoolColumn($name);
        $this->append($col);
        return $col;
    }

    public function setPrimaryKey(string $col, bool $defaultValueCheck = true): void
    {
        $column = $this->instances[$col] ?? null;
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

    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->instances);
    }
}
