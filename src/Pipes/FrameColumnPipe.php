<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Base\Support\Runtime;
use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\BufferImmutable;
use Charcoal\Buffers\Types\Bytes16;
use Charcoal\Buffers\Types\Bytes20;
use Charcoal\Buffers\Types\Bytes24;
use Charcoal\Buffers\Types\Bytes32;
use Charcoal\Buffers\Types\Bytes40;
use Charcoal\Buffers\Types\Bytes64;
use Charcoal\Contracts\Buffers\ByteArrayInterface;
use Charcoal\Contracts\Buffers\ReadableBufferInterface;
use Charcoal\Database\Orm\Contracts\ColumnValuePipeInterface;
use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;

/**
 * Store/Retrieve Fixed-Length+Immutable buffers from the database.
 */
final readonly class FrameColumnPipe implements ColumnValuePipeInterface
{
    public static function forDb(mixed $value, ColumnSnapshot $context): string
    {
        Runtime::assert($value instanceof ReadableBufferInterface,
            "FrameColumnPipe: value must be a ReadableBufferInterface, got " . get_debug_type($value));

        return $value->bytes();
    }

    public static function forEntity(string|int|array $value, ColumnSnapshot $context): ByteArrayInterface
    {
        Runtime::assert(is_string($value),
            "FrameColumnPipe: value must be a string, got " . get_debug_type($value));

        if (!is_int($context->byteLen)) {
            throw new \InvalidArgumentException("FrameColumnPipe: byteLen must be a positive integer");
        }

        return self::getPaddedFrame($value, $context);
    }

    private static function getPaddedFrame(string $value, ColumnSnapshot $column): ByteArrayInterface
    {
        if ($column->byteLen && $column->fixedLen === false) {
            return new Buffer($value);
        }

        return match ($column->byteLen) {
            16 => Bytes16::setPadded($value),
            20 => Bytes20::setPadded($value),
            24 => Bytes24::setPadded($value),
            32 => Bytes32::setPadded($value),
            40 => Bytes40::setPadded($value),
            64 => Bytes64::setPadded($value),
            default => new BufferImmutable($value),
        };
    }

    public static function validate(array $context): void
    {
    }
}