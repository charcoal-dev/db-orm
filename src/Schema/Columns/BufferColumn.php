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

/**
 * Class BufferColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class BufferColumn extends BlobColumn
{
    /**
     * @return void
     */
    protected function attributesCallback(): void
    {
        $this->attributes->setModelsValueResolver(Buffer::class);
        $this->attributes->setModelsValueDissolveFn(function (?AbstractByteArray $byteArray): ?string {
            return $byteArray?->raw();
        });
    }
}
