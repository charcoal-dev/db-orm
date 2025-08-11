<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\PrimitiveType;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Schema\Traits\NumericValueTrait;
use Charcoal\Database\Orm\Schema\Traits\UniqueValueTrait;

/**
 * Class IntegerColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class IntegerColumn extends AbstractColumn
{
    public const ?PrimitiveType PRIMITIVE_TYPE = PrimitiveType::INT;

    private int $size = 4;

    use NumericValueTrait;
    use UniqueValueTrait;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->attributes->unSigned = true;
    }

    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["size"] = $this->size;
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->size = $data["size"];
        unset($data["size"]);
        parent::__unserialize($data);
    }

    public function size(int $byte): static
    {
        if (!in_array($byte, [1, 2, 3, 4, 8])) {
            throw new \OutOfBoundsException('Invalid integer size');
        }

        $this->size = $byte;
        return $this;
    }

    public function bytes(int $byte): static
    {
        return $this->size($byte);
    }

    public function default(int $value): static
    {
        if ($value < 0 && $this->attributes["unsigned"] === 1) {
            throw new \InvalidArgumentException('Cannot set signed integer as default value');
        }

        $this->setDefaultValue($value);
        return $this;
    }

    public function autoIncrement(): static
    {
        $this->attributes->autoIncrement = true;
        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => match ($this->size) {
                1 => "tinyint",
                2 => "smallint",
                3 => "mediumint",
                8 => "bigint",
                default => "int",
            },
            default => "integer",
        };
    }
}
