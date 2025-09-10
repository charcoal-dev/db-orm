<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\ColumnAttributesBuilder;
use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;

/**
 * Class AbstractColumnBuilder
 * @package Charcoal\Database\Orm\Schema\Builder\Columns
 */
abstract class AbstractColumnBuilder
{
    use NotSerializableTrait;
    use NotCloneableTrait;

    protected readonly ColumnAttributesBuilder $attributes;

    /**
     * Column builder constructor.
     */
    public function __construct(public readonly string $name, ColumnType $type)
    {
        if (!$this->name || !preg_match('/^[A-Za-z0-9_]+$/', $this->name)) {
            throw new \InvalidArgumentException(sprintf('Column name "%s" is invalid', $this->name));
        }

        $this->attributes = new ColumnAttributesBuilder($name, $type);
    }

    /**
     * Implement/enforce data integrity checks for this column when generating SQL?
     */
    public function enforceChecks(bool $enforce = true): static
    {
        $this->attributes->enforceChecks = $enforce;
        return $this;
    }

    /**
     * Make this column nullable?
     */
    public function nullable(): static
    {
        $this->attributes->nullable = true;
        return $this;
    }

    /**
     * Sets the default value for this column.
     */
    protected function setDefaultValue(null|int|string|float $value): static
    {
        if (is_null($value) && !$this->attributes->nullable) {
            throw new \InvalidArgumentException(
                sprintf('Default value for col "%s" cannot be NULL; Column is not nullable',
                    $this->attributes->name)
            );
        }

        $this->attributes->defaultValue = $value;
        return $this;
    }

    /**
     * @internal
     */
    public function getAttributes(): ColumnAttributesBuilder
    {
        return $this->attributes;
    }

    /**
     * @internal
     */
    public function snapshot(string $schemaSql): ColumnSnapshot
    {
        $byteLen = null;
        $fixedLen = null;
        if ($this instanceof BinaryColumn || $this instanceof StringColumn) {
            $byteLen = $this->length;
            $fixedLen = $this->fixed;
        }

        $pipe = $this->attributes->getPipe();

        return new ColumnSnapshot(
            $this->attributes->name,
            $this->attributes->entityMapKey,
            $this->attributes->type,
            $this->attributes->nullable,
            $this->attributes->unSigned,
            $this->attributes->unique,
            $this->attributes->autoIncrement,
            $this->attributes->charset,
            $this->attributes->defaultValue,
            $byteLen,
            $fixedLen,
            $pipe[0],
            $pipe[1],
            $schemaSql
        );
    }

    /** @internal */
    abstract public function getColumnSQL(DbDriver $driver): ?string;

    /** @internal */
    abstract public function getCheckConstraint(): ?string;
}
