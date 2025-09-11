<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\Traits\NumericValueTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\PrecisionValueTrait;

/**
 * Class FloatColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class FloatColumn extends AbstractColumnBuilder
{
    protected const int MAX_DIGITS = 65;
    protected const int MAX_SCALE = 30;

    protected string $sqlType;
    private ?string $min = null;
    private ?string $max = null;

    use NumericValueTrait;
    use PrecisionValueTrait;

    public function __construct(string $name, ColumnType $type = ColumnType::Float)
    {
        parent::__construct($name, $type);
        $this->sqlType = "float";
        $this->setDefaultValue("0");
    }

    public function default(float|int $value = 0): static
    {
        $this->setDefaultValue($value);
        return $this;
    }

    public function range(float|int $min, float|int $max): static
    {
        if ($this->attributes->unSigned && ($min < 0 || $max < 0)) {
            throw new \InvalidArgumentException("Unsigned float cannot have negative range: " . $this->attributes->name);
        }
        if ($min > $max) {
            throw new \InvalidArgumentException("Minimum must be <= maximum: " . $this->attributes->name);
        }

        $this->min = (string)$min;
        $this->max = (string)$max;
        return $this;
    }


    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => (isset($this->digits, $this->scale)
                    ? sprintf("%s(%d,%d)", $this->sqlType, $this->digits, $this->scale)
                    : $this->sqlType)
                . ($this->attributes->unSigned ? " UNSIGNED" : ""),
            DbDriver::PGSQL,
            DbDriver::SQLITE => "REAL",
        };
    }

    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        if (!isset($this->min, $this->max)) {
            return null;
        }

        return sprintf(
            "CHECK (%s BETWEEN %s AND %s)",
            $this->attributes->name,
            $this->min,
            $this->max
        );
    }
}
