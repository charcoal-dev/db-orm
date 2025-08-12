<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm\Models;

use Charcoal\Buffers\Buffer;

class BlobModel
{
    public string $key;
    public Buffer $object;
    public ?string $matchExp;
    public int $timestamp;
}
