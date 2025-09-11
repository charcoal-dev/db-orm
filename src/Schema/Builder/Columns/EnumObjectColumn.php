<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Orm\Pipes\ColumnPipes;

/**
 * Enum that pipes value to/from actual PHP enums for entity classes.
 */
class EnumObjectColumn extends EnumColumn
{
    public function __construct(string $name, string $enumClass)
    {
        parent::__construct($name);
        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException("Enum class does not exist: " . $enumClass);
        }

        $this->attributes->useValuePipe(ColumnPipes::BackedEnumColumnPipe, ["enum" => $enumClass]);
    }
}
