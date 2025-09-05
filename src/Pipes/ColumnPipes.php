<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Database\Orm\Contracts\ColumnValuePipeEnumInterface;
use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;

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
    case BackedEnumColumnPipe;
    case FrameColumnPipe;

    public function forDb(mixed $value, ColumnSnapshot $context): string
    {
        return match ($this) {
            self::BufferColumnPipe => BufferColumnPipe::forDb($value, $context),
            self::BoolColumnPipe => BoolColumnPipe::forDb($value, $context),
            self::DateColumnPipe => DateColumnPipe::forDb($value, $context),
            self::DsvColumnPipe => DsvColumnPipe::forDb($value, $context),
            self::BackedEnumColumnPipe => BackedEnumColumnPipe::forDb($value, $context),
            self::FrameColumnPipe => FrameColumnPipe::forDb($value, $context),
        };
    }

    public function forEntity(string|int|array $value, ColumnSnapshot $context): mixed
    {
        return match ($this) {
            self::BufferColumnPipe => BufferColumnPipe::forEntity($value, $context),
            self::BoolColumnPipe => BoolColumnPipe::forEntity($value, $context),
            self::DateColumnPipe => DateColumnPipe::forEntity($value, $context),
            self::DsvColumnPipe => DsvColumnPipe::forEntity($value, $context),
            self::BackedEnumColumnPipe => BackedEnumColumnPipe::forEntity($value, $context),
            self::FrameColumnPipe => FrameColumnPipe::forEntity($value, $context),
        };
    }
}