<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\Traits\LargeObjectSizeTrait;

/**
 * Column for Binary Large Objects (BLOBs)
 */
class BlobColumn extends AbstractColumnBuilder
{
    use LargeObjectSizeTrait;

    public function __construct(string $name, ColumnType $type = ColumnType::Blob)
    {
        parent::__construct($name, $type);
    }

    /**
     * SQL declaration for BLOBs
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return $this->size->getColumnSQL($driver, text: false);
    }

    /**
     * No CHECK constraint for LOBs.
     */
    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        return null;
    }
}
