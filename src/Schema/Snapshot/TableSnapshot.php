<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Snapshot;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\MySqlEngine;

/**
 * Snapshot of table attributes.
 */
final readonly class TableSnapshot
{
    public array $entityProps;

    /**
     * @param ColumnSnapshot[] $columns
     * @param ConstraintSnapshot[] $constraints
     */
    public function __construct(
        public array        $columns,
        public array        $constraints,
        public ?string      $primaryKey,
        public DbDriver     $driver,
        public ?MySqlEngine $mySqlEngine,
    )
    {
        $entityProps = [];
        foreach ($columns as $column) {
            $entityProps[$column->entityMapKey] = $column->name;
        }

        $this->entityProps = $entityProps;
    }

    /**
     * Find a column by its name or normalized property name.
     */
    public function findColumn(string $name): ?ColumnSnapshot
    {
        if (!$name) {
            return null;
        }

        if (isset($this->entityProps[$name])) {
            return $this->columns[$this->entityProps[$name]];
        }

        return $this->columns[$name] ?? null;
    }
}