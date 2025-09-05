<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Traits;

use Charcoal\Database\Orm\Enums\LobSize;

/** @internal */
trait LargeObjectSizeTrait
{
    protected LobSize $size = LobSize::DEFAULT;

    final public function size(LobSize $size): static
    {
        $this->size = $size;
        return $this;
    }
}
