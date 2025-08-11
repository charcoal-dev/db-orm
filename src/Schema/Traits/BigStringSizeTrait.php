<?php
/*
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Traits;

/**
 * Trait BigStringSizeTrait
 * @package Charcoal\Database\Orm\Schema\Traits
 * @internal
 */
trait BigStringSizeTrait
{
    /**
     * @param string $size
     * @return $this
     */
    final public function size(string $size): static
    {
        $size = strtolower($size);
        if (!in_array($size, ["tiny", "", "medium", "long"])) {
            throw new \InvalidArgumentException('Bad column size, use Schema::SIZE_* flag');
        }

        $this->size = $size;
        return $this;
    }
}
