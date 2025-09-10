<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Pipes\ColumnPipes;

/**
 * Class DsvColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class DsvColumn extends StringColumn
{
    public function __construct(string $name, ?string $delimiter = null)
    {
        parent::__construct($name, ColumnType::Dsv);
        $this->attributes->useValuePipe(ColumnPipes::DsvColumnPipe);
        if (!is_null($delimiter)) {
            $this->delimiter($delimiter);
        }
    }

    /**
     * @param string $delimiter
     * @return $this
     */
    public function delimiter(string $delimiter): static
    {
        if (!in_array($delimiter, [" ", ",", "\t", "|", ";", ":"])) {
            throw new \InvalidArgumentException("Invalid delimiter");
        }

        $this->attributes->updateContext(["delimiter" => $delimiter]);
        return $this;
    }

    /**
     * Define the enum class for the column;
     * Validates that value is one of the cases declared in enum, when storing in DB.
     * @param class-string<\StringBackedEnum> $enumClass
     * @return $this
     * @api
     */
    public function enumClass(string $enumClass): static
    {
        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException("Enum class does not exist: " . $enumClass);
        }

        $this->attributes->updateContext(["enum" => $enumClass]);
        return $this;
    }
}