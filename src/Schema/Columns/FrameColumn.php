<?php
/*
 * This file is a part of "charcoal-dev/db-orm" package.
 * https://github.com/charcoal-dev/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/db-orm/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Database\ORM\Schema\Columns;

use Charcoal\Buffers\AbstractByteArray;
use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\Frames\Bytes20P;
use Charcoal\Buffers\Frames\Bytes32P;
use Charcoal\Buffers\Frames\Bytes64P;

/**
 * Class FrameColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class FrameColumn extends BinaryColumn
{
    /**
     * @return void
     */
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

        $this->attributes->setModelsValueResolver($bufferClass);
        $this->attributes->setModelsValueDissolveFn(function (?AbstractByteArray $byteArray): ?string {
            return $byteArray?->raw();
        });
    }
}
