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
 * Trait PrecisionValueTrait
 * @package Charcoal\Database\ORM\Schema\Traits
 * @internal
 */
trait PrecisionValueTrait
{
    /**
     * @param int $digits
     * @param int $scale
     * @return $this
     */
    public function precision(int $digits, int $scale): static
    {
        // Precision digits
        if ($digits < 1 || $digits > static::MAX_DIGITS) {
            throw new \OutOfRangeException(
                sprintf('Precision digits must be between 1 and %d', static::MAX_DIGITS)
            );
        }

        // Scale
        $maxScale = max($digits, static::MAX_SCALE);
        if ($scale < 0 || $scale > $maxScale) {
            throw new \OutOfRangeException(
                sprintf('Scale digits must be between 1 and %d', $maxScale)
            );
        }


        // Set
        $this->digits = $digits;
        $this->scale = $scale;
        return $this;
    }
}
