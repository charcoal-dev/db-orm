<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Database\Orm\Contracts\ColumnValuePipeEnumInterface;

/**
 * Enum representing column pipe transformations.
 * Provides methods to handle database and entity-specific value transformations.
 */
enum ColumnPipes implements ColumnValuePipeEnumInterface
{
    case BufferColumnPipe;
    case BoolColumnPipe;
    case DateColumnPipe;
    case DsvColumnPipe;

    public function forDb(mixed $value): string
    {
        return match ($this) {
            self::BufferColumnPipe => BufferColumnPipe::forDb($value),
            self::BoolColumnPipe => BoolColumnPipe::forDb($value),
            self::DateColumnPipe => DateColumnPipe::forDb($value),
            self::DsvColumnPipe => DsvColumnPipe::forDb($value),
        };
    }

    public function forEntity(string|int $value): mixed
    {
        return match ($this) {
            self::BufferColumnPipe => BufferColumnPipe::forEntity($value),
            self::BoolColumnPipe => BoolColumnPipe::forEntity($value),
            self::DateColumnPipe => DateColumnPipe::forEntity($value),
            self::DsvColumnPipe => DsvColumnPipe::forEntity($value),
        };
    }
}