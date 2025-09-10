<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Adapters\Gmp\Support\BigIntHelper;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\Traits\NumericValueTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\UniqueValueTrait;

/**
 * Class IntegerColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class IntegerColumn extends AbstractColumnBuilder
{
    private ?int $size = null;
    private ?string $min = null;
    private ?string $max = null;

    use NumericValueTrait;
    use UniqueValueTrait;

    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Integer);
    }

    /**
     * Define integer column using a range for storable values;
     * Automatically determines the size of the column based on the range, and implements CHECK too.
     */
    public function range(int|string $min, int|string $max): static
    {
        if (isset($this->size) || isset($this->attributes->unSigned)) {
            throw new \InvalidArgumentException("Cannot set range for integer column with size defined; " .
                "Use either size() or range()");
        }

        $min = gmp_init($min, 10);
        $max = gmp_init($max, 10);
        if (gmp_cmp($min, $max) > 0) {
            throw new \InvalidArgumentException("Minimum value must be less than maximum value");
        }

        $this->attributes->unSigned = gmp_cmp($min, "0") >= 0;
        $this->size = match ($this->attributes->unSigned) {
            true => match (true) {
                (gmp_cmp($max, "255") <= 0) => 1,
                (gmp_cmp($max, "65535") <= 0) => 2,
                (gmp_cmp($max, "4294967295") <= 0) => 4,
                (gmp_cmp($max, "18446744073709551615") <= 0) => 8,
                default => throw new \OverflowException("Maximum value too large for unsigned integer"),
            },
            false => match (true) {
                (gmp_cmp($min, "-128") >= 0 && gmp_cmp($max, "127") <= 0) => 1,
                (gmp_cmp($min, "-32768") >= 0 && gmp_cmp($max, "32767") <= 0) => 2,
                (gmp_cmp($min, "-2147483648") >= 0 && gmp_cmp($max, "2147483647") <= 0) => 4,
                (gmp_cmp($min, "-9223372036854775808") >= 0 && gmp_cmp($max, "9223372036854775807") <= 0) => 8,
                default => throw new \OutOfBoundsException("Bad range for signed integer"),
            }
        };

        $this->min = gmp_strval($min, 10);
        $this->max = gmp_strval($max, 10);
        return $this;
    }

    /**
     * Not deprecated, but prefer using range() instead.
     */
    public function size(int $byteLen): static
    {
        if (isset($this->min) || isset($this->max)) {
            throw new \InvalidArgumentException("Cannot set size for integer column with range defined; " .
                "Use either size() or range()");
        }

        if (is_null($this->attributes->unSigned)) {
            $this->attributes->unSigned = true; // Assume unsigned by default
        }

        if (!in_array($byteLen, [1, 2, 3, 4, 8])) {
            throw new \OutOfBoundsException("Invalid integer size");
        }

        $this->size = $byteLen;
        return $this;
    }

    /**
     * Set default value for integer column.
     */
    public function default(int $value): static
    {
        if (!isset($this->size) && !isset($this->min, $this->max)) {
            throw new \LogicException("Cannot set default value without size or range for column: " .
                $this->attributes->name);
        }

        if (isset($this->min, $this->max)) {
            if (!BigIntHelper::inRange($value, $this->min, $this->max)) {
                throw new \OutOfBoundsException("Value out of range for integer column:" .
                    $this->attributes->name);
            }

            $this->setDefaultValue($value);
            return $this;
        }

        if (!BigIntHelper::inRange($value, match ($this->size) {
            1 => $this->attributes->unSigned ? "0" : "-128",
            2 => $this->attributes->unSigned ? "0" : "-32768",
            4 => $this->attributes->unSigned ? "0" : "-2147483648",
            default => $this->attributes->unSigned ? "0" : "-9223372036854775808",
        }, match ($this->size) {
            1 => $this->attributes->unSigned ? "255" : "127",
            2 => $this->attributes->unSigned ? "65535" : "32767",
            4 => $this->attributes->unSigned ? "4294967295" : "2147483647",
            default => $this->attributes->unSigned ? "18446744073709551615" : "9223372036854775807",
        })) {
            throw new \OutOfBoundsException(
                "Value out of range for integer column:" . $this->attributes->name);
        }

        $this->setDefaultValue($value);
        return $this;
    }

    /**
     * @api auto_increment
     */
    public function autoIncrement(): static
    {
        $this->attributes->autoIncrement = true;
        return $this;
    }

    /**
     * @param DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        if (is_null($this->size) && (is_null($this->min) || is_null($this->max))) {
            throw new \LogicException("Cannot get column SQL without size or range for column: " .
                $this->attributes->name);
        }

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

    /**
     * The check constraint for the column.
     */
    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        if (!isset($this->min, $this->max)) {
            return null;
        }

        return "CHECK (`" . $this->attributes->name . "` BETWEEN " . $this->min . " AND " . $this->max . ")";
    }
}