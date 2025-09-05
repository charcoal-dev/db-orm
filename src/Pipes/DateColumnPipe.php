<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Base\Support\Runtime;
use Charcoal\Database\Orm\Contracts\ColumnValuePipeInterface;

/**
 * Store/Retrieve \DateTime values from the database.
 */
final readonly class DateColumnPipe implements ColumnValuePipeInterface
{
    public static function forDb(mixed $value): string
    {
        Runtime::assert($value instanceof \DateTime,
            "DateColumnPipe: value must be a DateTime, got " . get_debug_type($value));

        return $value->format("Y-m-d");
    }

    public static function forEntity(int|string $value): \DateTime
    {
        Runtime::assert(is_string($value),
            "DateColumnPipe: value must be a string, got " . get_debug_type($value));

        return \DateTime::createFromFormat("Y-m-d", $value);
    }
}