<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Enums\LobSize;
use Charcoal\Database\Orm\Schema\Builder\Traits\ColumnCharsetTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\LargeObjectSizeTrait;

/**
 * Column for Textual Data (CLOBs)
 */
class TextColumn extends AbstractColumnBuilder
{
    protected LobSize $size = LobSize::DEFAULT;

    use ColumnCharsetTrait;
    use LargeObjectSizeTrait;


    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Text);
    }

    /**
     * SQL declaration for CLOBs
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return $this->size->getColumnSQL($driver, text: true);
    }

    /**
     * No CHECK constraint for LOBs.
     */
    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        return null;
    }
}
