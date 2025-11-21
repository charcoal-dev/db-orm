<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;

/**
 * Represents a column specific to handling JSON data types.
 * This class provides methods to define the SQL data type
 * for supported database drivers as well as associated constraints.
 */
final class JsonColumn extends AbstractColumnBuilder
{
    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Json);
    }

    /**
     * @internal
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => "JSON",
            DbDriver::SQLITE => "JSONB",
            DbDriver::PGSQL => "TEXT",
        };
    }

    /**
     * @internal
     */
    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        return null;
    }
}