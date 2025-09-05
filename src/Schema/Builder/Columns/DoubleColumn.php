<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Orm\Enums\ColumnType;

/**
 * Class DoubleColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class DoubleColumn extends FloatColumn
{
    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Double);
        $this->sqlType = "double";
    }
}

