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
 * @property ColumnSnapshot[] $columns
 * @property ConstraintSnapshot[] $constraints
 */
final readonly class TableSnapshot
{
    public array $entityProps;

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
            $entityProps[$column->entityProp] = $column->name;
        }

        $this->entityProps = $entityProps;
    }
}