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
 * Class DecimalColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class DecimalColumn extends AbstractColumnBuilder
{
    protected const int MAX_DIGITS = 65;
    protected const int MAX_SCALE = 30;

    use NumericValueTrait;
    use PrecisionValueTrait;


    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Decimal);
        $this->setDefaultValue("0");
    }

    public function default(string $value = "0"): static
    {
        if (!preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Bad default decimal value for col "%s"',
                $this->attributes->name));
        }

        $this->setDefaultValue($value);
        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => sprintf("decimal(%d,%d)", $this->digits, $this->scale),
            DbDriver::PGSQL => "REAL",
            default => null,
        };
    }
}
