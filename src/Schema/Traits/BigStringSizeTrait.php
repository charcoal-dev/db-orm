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

namespace Charcoal\Database\ORM\Schema\Traits;

/**
 * Trait BigStringSizeTrait
 * @package Charcoal\Database\ORM\Schema\Traits
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
