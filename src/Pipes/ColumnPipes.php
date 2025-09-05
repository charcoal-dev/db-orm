<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

/**
 * Enum representing column pipe transformations.
 * Provides methods to handle database and entity-specific value transformations.
 */
enum ColumnPipes
{
    case BufferColumnPipe;

    public function forDb(mixed $value): string
    {
        return match ($this) {
            self::BufferColumnPipe => BufferColumnPipe::forDb($value)
        };
    }

    public function forEntity(string|int $value): mixed
    {
        return match ($this) {
            self::BufferColumnPipe => BufferColumnPipe::forEntity($value)
        };
    }
}