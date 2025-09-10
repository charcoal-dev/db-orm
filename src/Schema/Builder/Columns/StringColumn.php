<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Orm\Enums\ColumnType;

/**
 * CHAR/VARCHAR column definition
 */
class StringColumn extends AbstractStringColumn
{
    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::String);
    }
}
