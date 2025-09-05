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
 * Class BoolColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class BoolColumn extends AbstractColumnBuilder
{
    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Bool);
    }

    public function default(bool $defaultValue): static
    {
        $this->setDefaultValue($defaultValue ? 1 : 0);
        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => "tinyint",
            default => "integer",
        };
    }
}

