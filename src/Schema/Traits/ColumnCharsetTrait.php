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
 * Trait ColumnCharsetTrait
 * @package Charcoal\Database\ORM\Schema\Traits
 */
trait ColumnCharsetTrait
{
    /**
     * @param string $charset
     * @return $this
     */
    final public function charset(string $charset): static
    {
        $this->attributes->charset = $charset;
        return $this;
    }

    /**
     * @param string $collate
     * @return $this
     */
    final public function collation(string $collate): static
    {
        $this->attributes->charsetCollation = $collate;
        return $this;
    }
}

