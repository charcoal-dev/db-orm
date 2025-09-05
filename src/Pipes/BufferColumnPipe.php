<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Base\Support\Runtime;
use Charcoal\Buffers\Buffer;
use Charcoal\Contracts\Buffers\ReadableBufferInterface;
use Charcoal\Database\Orm\Contracts\ColumnValuePipeInterface;

/**
 * Store/Retrieve Buffer values from database
 */
final readonly class BufferColumnPipe implements ColumnValuePipeInterface
{
    public static function forDb(mixed $value): string
    {
        Runtime::assert($value instanceof ReadableBufferInterface,
            "BufferColumnPipe: value must be a ReadableBufferInterface, got " . get_debug_type($value));

        return $value->bytes();
    }

    public static function forEntity(string|int $value): Buffer
    {
        Runtime::assert(is_string($value),
            "BufferColumnPipe: value must be a string, got " . get_debug_type($value));

        return new Buffer($value);
    }
}