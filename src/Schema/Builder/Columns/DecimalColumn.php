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

    private ?string $min = null;
    private ?string $max = null;

    use NumericValueTrait;
    use PrecisionValueTrait;

    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Decimal);
        $this->setDefaultValue("0");
    }

    /**
     * Set the default value (string, base-10).
     */
    public function default(string $value = "0"): static
    {
        if (!preg_match("/^-?[0-9]+(?:\.[0-9]+)?$/", $value)) {
            throw new \InvalidArgumentException(sprintf("Bad default decimal value for col \"%s\"",
                $this->attributes->name));
        }

        if ($this->attributes->unSigned && bccomp($value, "0", $this->inferScale($value)) < 0) {
            throw new \OutOfBoundsException("Default must be >= 0 for unsigned decimal: " . $this->attributes->name);
        }

        if (isset($this->digits, $this->scale)) {
            $norm = $this->normalizeToScale($value, $this->scale);
            $this->validatePrecision($norm, $this->digits, $this->scale);

            if (isset($this->min, $this->max)) {
                if (!$this->inRangeBc($norm, $this->min, $this->max, $this->scale)) {
                    throw new \OutOfBoundsException("Default value out of range for decimal column: " .
                        $this->attributes->name);
                }
            }

            $value = $norm;
        }

        $this->setDefaultValue($value);
        return $this;
    }

    /**
     * Define an inclusive range for values. Uses BCMath for comparison.
     * Accepts strings in base-10. Precision checks are applied if digits/scale are already known.
     */
    public function range(string $min, string $max): static
    {
        if (!preg_match("/^-?[0-9]+(?:\.[0-9]+)?$/", $min)
            || !preg_match("/^-?[0-9]+(?:\.[0-9]+)?$/", $max)) {
            throw new \InvalidArgumentException("Bad decimal range for column: " . $this->attributes->name);
        }

        if ($this->attributes->unSigned && (bccomp($min, "0") < 0 || bccomp($max, "0") < 0)) {
            throw new \InvalidArgumentException("Unsigned decimal cannot have negative range: " .
                $this->attributes->name);
        }

        // pick a comparison scale that preserves both inputs (and current scale if set)
        $cmpScale = max($this->inferScale($min), $this->inferScale($max), $this->scale ?? 0);
        if (bccomp($min, $max, $cmpScale) > 0) {
            throw new \InvalidArgumentException('Minimum value must be less than or equal to maximum value: ' . $this->attributes->name);
        }

        // If precision known, enforce bounds fit precision
        if (isset($this->digits, $this->scale)) {
            $minN = $this->normalizeToScale($min, $this->scale);
            $maxN = $this->normalizeToScale($max, $this->scale);
            $this->validatePrecision($minN, $this->digits, $this->scale);
            $this->validatePrecision($maxN, $this->digits, $this->scale);
            $this->min = $minN;
            $this->max = $maxN;
            return $this;
        }

        // Defer precision enforcement until digits/scale are set
        $this->min = $min;
        $this->max = $max;
        return $this;
    }

    /**
     * Column SQL.
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        if (!isset($this->digits, $this->scale)) {
            throw new \LogicException('Cannot get decimal column SQL without precision for column: ' .
                $this->attributes->name);
        }

        return match ($driver) {
            DbDriver::MYSQL => sprintf("decimal(%d,%d)", $this->digits, $this->scale),
            DbDriver::PGSQL => sprintf("numeric(%d,%d)", $this->digits, $this->scale),
            default => "numeric",
        };
    }

    /**
     * The CHECK constraint for the column (only if range was set).
     * Emits a simple min/max check; precision is enforced by the type in MySQL/PG, and by affinity+checks in SQLite.
     */
    public function getCheckConstraint(): ?string
    {
        if (!isset($this->min, $this->max)) {
            return null;
        }

        return "CHECK (`"
            . $this->attributes->name
            . "` BETWEEN "
            . $this->min . " AND " . $this->max
            . ")";
    }

    /**
     * Normalize a value to a scale, using BCMath.
     */
    private function normalizeToScale(string $value, int $scale): string
    {
        return bcmul($value, "1", $scale);
    }

    /**
     * Infer the scale of a value.
     */
    private function inferScale(string $value): int
    {
        $dec = strpos($value, ".");
        return $dec === false ? 0 : ((strlen($value) - $dec) - 1);
    }

    /**
     * Validate a value against the declared precision.
     */
    private function validatePrecision(string $value, int $digits, int $scale): void
    {
        if ($digits < 1 || $digits > self::MAX_DIGITS) {
            throw new \OutOfBoundsException("Bad digits for decimal: " . $digits);
        }

        if ($scale < 0 || $scale > self::MAX_SCALE || $scale > $digits) {
            throw new \OutOfBoundsException("Bad scale for decimal: " . $scale);
        }

        if (strlen(str_replace(".", "", ltrim($this->normalizeToScale($value, $scale), "-"))) > $digits) {
            throw new \OutOfBoundsException("Decimal value exceeds declared precision for column: " .
                $this->attributes->name);
        }
    }

    /**
     * Check if a value is within a range, using BCMath.
     */
    private function inRangeBc(string $v, string $min, string $max, int $scale): bool
    {
        $value = $this->normalizeToScale($v, $scale);
        $min = $this->normalizeToScale($min, $scale);
        $max = $this->normalizeToScale($max, $scale);
        return bccomp($value, $min, $scale) >= 0 && bccomp($value, $max, $scale) <= 0;
    }
}
