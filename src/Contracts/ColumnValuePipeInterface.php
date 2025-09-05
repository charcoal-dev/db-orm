<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Contracts;

/**
 * Interface for resolving column values for different contexts.
 */
interface ColumnValuePipeInterface
{
    public static function forDb(mixed $value): string;

    public static function forEntity(string|int $value): mixed;
}