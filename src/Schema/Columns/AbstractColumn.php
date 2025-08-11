<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\PrimitiveType;
use Charcoal\Database\Enums\DbDriver;

/**
 * Class AbstractColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
abstract class AbstractColumn
{
    public const ?PrimitiveType PRIMITIVE_TYPE = null;

    public readonly ColumnAttributes $attributes;

    public function __construct(string $name)
    {
        $this->attributes = new ColumnAttributes($name);
        $this->attributesCallback();
    }

    protected function attributesCallback(): void
    {
    }

    public function __serialize(): array
    {
        return ["attributes" => $this->attributes];
    }

    public function __unserialize(array $data): void
    {
        $this->attributes = $data["attributes"];
        $this->attributesCallback();
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

    abstract public function getColumnSQL(DbDriver $driver): ?string;
}
