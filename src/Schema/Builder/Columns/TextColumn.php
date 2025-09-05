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
 * Class TextColumn
 * @package Charcoal\Database\Orm\Schema\Columns
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

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => $this->size->getColumn($driver, text: true),
            default => "TEXT",
        };
    }
}
