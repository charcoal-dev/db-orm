<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\PrimitiveType;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Schema\Traits\UniqueValueTrait;

/**
 * Class DateColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class DateColumn extends AbstractColumn
{
    public const PrimitiveType PRIMITIVE_TYPE = PrimitiveType::STRING;

    use UniqueValueTrait;

    final public function default(\DateTime|int|string $value): static
    {
        if (is_string($value)) {
            $value = strtotime($value);
        }

        $this->setDefaultValue(match (true) {
            is_int($value) && $value > 0 => date("Y-m-d", $value),
            $value instanceof \DateTime => $value->format("Y-m-d"),
            default => throw new \InvalidArgumentException('Invalid type for date value'),
        });

        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL, DbDriver::PGSQL => "DATE",
            default => "TEXT",
        };
    }

    protected function attributesCallback(): void
    {
        $this->attributes->resolveTypedValue(function (?string $value): ?\DateTime {
            return $value ? \DateTime::createFromFormat("Y-m-d", $value) : null;
        });

        $this->attributes->resolveDbValue(function (?\DateTime $date): ?string {
            return $date?->format("Y-m-d");
        });
    }
}