<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\ColumnAttributesBuilder;

/**
 * Class AbstractColumnBuilder
 * @package Charcoal\Database\Orm\Schema\Builder\Columns
 */
abstract class AbstractColumnBuilder
{
    protected readonly ColumnAttributesBuilder $attributes;

    public function __construct(string $name, ColumnType $type)
    {
        $this->attributes = new ColumnAttributesBuilder($name, $type);
    }

    public function nullable(): static
    {
        $this->attributes->nullable = true;
        return $this;
    }

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

    /** @internal */
    abstract public function getColumnSQL(DbDriver $driver): ?string;
}
