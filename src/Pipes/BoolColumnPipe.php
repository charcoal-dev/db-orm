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
 * Store/Retrieve booleans from the database
 */
final readonly class BoolColumnPipe implements ColumnValuePipeInterface
{
    /**
     * @param mixed $value
     * @return int
     */
    public static function forDb(mixed $value): int
    {
        Runtime::assert(is_bool($value),
            "BoolColumnPipe: value must be a bool, got " . get_debug_type($value));

        return $value ? 1 : 0;
    }

    /**
     * @param string|int $value
     * @return bool
     */
    public static function forEntity(string|int $value): bool
    {
        Runtime::assert(is_int($value),
            "BoolColumnPipe: value must be a integer, got " . get_debug_type($value));

        return $value === 1;
    }
}