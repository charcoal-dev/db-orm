<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Buffers\AbstractByteArray;
use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\Frames\Bytes20P;
use Charcoal\Buffers\Frames\Bytes32P;
use Charcoal\Buffers\Frames\Bytes64P;

/**
 * Class FrameColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class FrameColumn extends BinaryColumn
{
    protected function attributesCallback(): void
    {
        $bufferClass = Buffer::class;
        if ($this->fixed) {
            $bufferClass = match ($this->length) {
                20 => Bytes20P::class,
                32 => Bytes32P::class,
                64 => Bytes64P::class,
                default => Buffer::class
            };
        }

        $this->attributes->resolveTypedValue($bufferClass);
        $this->attributes->resolveDbValue(function (?AbstractByteArray $byteArray): ?string {
            return $byteArray?->raw();
        });
    }
}
