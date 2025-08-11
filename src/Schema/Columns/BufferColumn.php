<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Buffers\AbstractByteArray;
use Charcoal\Buffers\Buffer;

/**
 * Class BufferColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class BufferColumn extends BlobColumn
{
    protected function attributesCallback(): void
    {
        $this->attributes->resolveTypedValue(Buffer::class);
        $this->attributes->resolveDbValue(function (?AbstractByteArray $byteArray): ?string {
            return $byteArray?->raw();
        });
    }
}
