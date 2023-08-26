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
 * Trait LengthValueTrait
 * @package Charcoal\Database\ORM\Schema\Traits
 */
trait LengthValueTrait
{
    /**
     * @param int $length
     * @return $this
     */
    public function length(int $length): static
    {
        if ($length < static::LENGTH_MIN || $length > static::LENGTH_MAX) {
            throw new \OutOfRangeException(
                sprintf('Maximum length for col "%s" cannot exceed %d', $this->attributes->name, static::LENGTH_MAX)
            );
        }

        $this->length = $length;
        return $this;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function fixed(int $length): static
    {
        $this->length($length);
        $this->fixed = true;
        return $this;
    }
}
