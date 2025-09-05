<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Base\Support\Runtime;
use Charcoal\Buffers\BufferImmutable;
use Charcoal\Buffers\Types\Bytes16;
use Charcoal\Buffers\Types\Bytes20;
use Charcoal\Buffers\Types\Bytes24;
use Charcoal\Buffers\Types\Bytes32;
use Charcoal\Buffers\Types\Bytes40;
use Charcoal\Buffers\Types\Bytes64;
use Charcoal\Contracts\Buffers\Immutable\ImmutableBufferInterface;
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

    public static function forEntity(string|int|array $value, ColumnSnapshot $context): ImmutableBufferInterface
    {
        Runtime::assert(is_string($value),
            "FrameColumnPipe: value must be a string, got " . get_debug_type($value));

        if (!is_int($context->byteLen)) {
            throw new \InvalidArgumentException("FrameColumnPipe: byteLen must be a positive integer");
        }

        $fqcn = match ($context->byteLen) {
            16 => Bytes16::class,
            20 => Bytes20::class,
            24 => Bytes24::class,
            32 => Bytes32::class,
            40 => Bytes40::class,
            64 => Bytes64::class,
            default => BufferImmutable::class,
        };

        return new $fqcn($value);
    }

    public static function validate(array $context): void
    {
    }
}