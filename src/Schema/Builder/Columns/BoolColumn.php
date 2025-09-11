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

/**
 * Boolean column (backed by tinyint)
 */
final class BoolColumn extends AbstractColumnBuilder
{
    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Bool);
        $this->attributes->unSigned = true;
        $this->attributes->useValuePipe(ColumnPipes::BoolColumnPipe);
    }

    /**
     * Set the default value (boolean).
     */
    public function default(bool $defaultValue): self
    {
        $this->setDefaultValue($defaultValue ? 1 : 0);
        return $this;
    }

    /**
     * @internal Bool columns are based on primitive type unsigned-integer.
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => "tinyint",
            default => "integer",
        };
    }

    /**
     * @internal The check constraint for the column.
     */
    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        return "CHECK (" . $this->attributes->name . " IN (0,1))";
    }
}

