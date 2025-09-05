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

    use NumericValueTrait;
    use PrecisionValueTrait;

    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Float);
        $this->sqlType = "float";
        $this->setDefaultValue("0");
    }

    public function default(float|int $value = 0): static
    {
        $this->setDefaultValue($value);
        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => sprintf("%s(%d,%d)", $this->sqlType, $this->digits, $this->scale),
            DbDriver::PGSQL => "REAL",
            default => null,
        };
    }
}
