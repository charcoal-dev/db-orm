<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\Traits\ColumnCharsetTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\LengthValueTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\StringValueTrait;
use Charcoal\Database\Orm\Schema\Builder\Traits\UniqueValueTrait;

/**
 * Class StringColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class StringColumn extends AbstractColumnBuilder
{
    protected const int LENGTH_MIN = 1;
    protected const int LENGTH_MAX = 0xffff;

    use ColumnCharsetTrait;
    use LengthValueTrait;
    use StringValueTrait;
    use UniqueValueTrait;

    public function __construct(string $name, ColumnType $type = ColumnType::String)
    {
        parent::__construct($name, $type);
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => sprintf('%s(%d)', ($this->fixed ? "char" : "varchar"), $this->length),
            default => "TEXT"
        };
    }
}
