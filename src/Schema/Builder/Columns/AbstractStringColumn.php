<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\CharcoalOrm;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\Traits\ColumnCharsetTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\LengthValueTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\StringValueTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\UniqueValueTrait;

/**
 * Abstraction for string-based columns.
 */
abstract class AbstractStringColumn extends AbstractColumnBuilder
{
    protected const int LENGTH_MIN = 1;
    protected const int LENGTH_MAX = 65535;

    use ColumnCharsetTrait;
    use LengthValueTrait;
    use StringValueTrait;
    use UniqueValueTrait;

    private ?int $minLength = null;
    private ?int $maxLength = null;
    private ?string $matchRegExp = null;

    protected function __construct(
        string     $name,
        ColumnType $type = ColumnType::String
    )
    {
        parent::__construct($name, $type);
    }

    /**
     * Match storing values against a regular expression.
     * @api
     */
    public function matchRegExp(string $regExp, string $delimiter = "/"): static
    {
        if (strlen($delimiter) !== 1) {
            throw new \InvalidArgumentException("RegExp delimiter must be a single character");
        }

        if (!$regExp || @preg_match($regExp, "") === false) {
            throw new \InvalidArgumentException("Bad RegExp pattern for column: " .
                $this->attributes->name);
        }

        $this->matchRegExp = str_replace("'", "''", $regExp);
        return $this;
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
                "(" . $this->length . " bytes) " .
                $this->attributes->name);
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
            DbDriver::PGSQL,
            DbDriver::MYSQL => sprintf("%s(%d)", ($this->fixed ? "char" : "varchar"), $this->length),
            default => "TEXT"
        };
    }

    /**
     * @internal
     */
    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        $checks = [];

        // Length Guard
        $lengthFn = $driver === DbDriver::SQLITE ? "LENGTH" : "CHAR_LENGTH";
        if (isset($this->minLength) || isset($this->maxLength)) {
            if ($this->fixed) {
                throw new \InvalidArgumentException("Cannot set min/max length for fixed-length string column");
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

        // RegExp Guard
        if ($this->matchRegExp) {
            if ($driver === DbDriver::SQLITE && !CharcoalOrm::$sqLiteEmitRegExp) {
                goto allChecksCollected;
            }

            $matchRegExp = CharcoalOrm::getConstraintRegExp($this->attributes->name, $this->matchRegExp);
            $checks[] = "REGEXP_LIKE(" . $this->attributes->name . ", '"
                . $matchRegExp[0] . "'" . ($matchRegExp[1] ? ", '" . $matchRegExp[1] . "'" : "") . ")";
        }

        allChecksCollected:
        if (!$checks) {
            return null;
        }

        return "CHECK (" . implode(" AND ", $checks) . ")";
    }
}