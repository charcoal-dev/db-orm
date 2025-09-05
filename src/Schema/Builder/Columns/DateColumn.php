<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Pipes\ColumnPipes;
use Charcoal\Database\Orm\Schema\Builder\Traits\UniqueValueTrait;

/**
 * Class DateColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class DateColumn extends AbstractColumnBuilder
{
    use UniqueValueTrait;

    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Date);
        $this->attributes->useValuePipe(ColumnPipes::DateColumnPipe);
    }

    final public function default(\DateTime|int|string $value): static
    {
        if (is_string($value)) {
            $value = strtotime($value);
        }

        $this->setDefaultValue(match (true) {
            is_int($value) && $value > 0 => date("Y-m-d", $value),
            $value instanceof \DateTime => $value->format("Y-m-d"),
            default => throw new \InvalidArgumentException("Invalid type for date value"),
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
}