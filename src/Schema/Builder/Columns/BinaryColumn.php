<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\Traits\LengthValueTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\StringValueTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\UniqueValueTrait;

/**
 * Class BinaryColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class BinaryColumn extends AbstractColumnBuilder
{
    protected const int LENGTH_MIN = 1;
    protected const int LENGTH_MAX = 65535;

    private ?int $minLength = null;
    private ?int $maxLength = null;

    use LengthValueTrait;
    use StringValueTrait;
    use UniqueValueTrait;

    public function __construct(string $name, ColumnType $type = ColumnType::Binary)
    {
        parent::__construct($name, $type);
    }

    /**
     * Length check constraint for storing values.
     * @api
     */
    public function checkLength(int $min = 0, int $max = 0): static
    {
        if ($min < 1 || ($max && $min > $max)) {
            throw new \InvalidArgumentException("Invalid length range for column: " .
                $this->attributes->name);
        }

        if ($min > $this->length) {
            throw new \InvalidArgumentException("Minimum length must be less than or equal to column length: " .
                "(" . $this->length . " bytes) " . $this->attributes->name);
        } else if ($max && $max > $this->length) {
            throw new \InvalidArgumentException("Maximum length must not exceed column length: " .
                "(" . $this->length . " bytes) " . $this->attributes->name);
        }

        $this->minLength = $min;
        $this->maxLength = $max;
        return $this;
    }

    /**
     * @internal
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => sprintf("%s(%d)", ($this->fixed ? "binary" : "varbinary"), $this->length),
            DbDriver::SQLITE => "BLOB",
            DbDriver::PGSQL => "BYTEA"
        };
    }

    /**
     * @internal
     */
    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        $checks = [];

        // Length Guard
        $lengthFn = $driver === DbDriver::SQLITE ? "LENGTH" : "OCTET_LENGTH";
        if (isset($this->minLength) || isset($this->maxLength)) {
            if ($this->fixed) {
                throw new \InvalidArgumentException("Cannot set min/max length for fixed-length binary column");
            }

            $min = $this->minLength ?? 0;
            $max = $this->maxLength ?? 0;
            if ($min && $max) {
                $checks[] = $lengthFn . "(" . $this->attributes->name . ") BETWEEN " .
                    $this->minLength . " AND " . $this->maxLength;
            } elseif ($min) {
                $checks[] = $lengthFn . "(" . $this->attributes->name . ") >= " . $this->minLength;
            }
        }

        if ($this->fixed) {
            $checks[] = $lengthFn . "(" . $this->attributes->name . ") = " . $this->length;
        }

        if (!$checks) {
            return null;
        }

        return "CHECK (" . implode(" AND ", $checks) . ")";
    }
}
