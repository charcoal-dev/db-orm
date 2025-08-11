<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Traits;

use Charcoal\Database\Orm\Concerns\LobSize;

/**
 * Trait LargeObjectSizeTrait
 * @package Charcoal\Database\Orm\Schema\Traits
 * @internal
 */
trait LargeObjectSizeTrait
{
    final public function size(LobSize $size): static
    {
        $this->size = $size;
        return $this;
    }
}
