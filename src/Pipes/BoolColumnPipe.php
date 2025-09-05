<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Base\Support\Runtime;
use Charcoal\Database\Orm\Contracts\ColumnValuePipeInterface;
use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;

/**
 * Store/Retrieve booleans from the database
 */
final readonly class BoolColumnPipe implements ColumnValuePipeInterface
{
    public static function forDb(mixed $value, ColumnSnapshot $context): int
    {
        Runtime::assert(is_bool($value),
            "BoolColumnPipe: value must be a bool, got " . get_debug_type($value));

        return $value ? 1 : 0;
    }

    public static function forEntity(string|int|array $value, ColumnSnapshot $context): bool
    {
        Runtime::assert(is_int($value),
            "BoolColumnPipe: value must be a integer, got " . get_debug_type($value));

        return $value === 1;
    }
}