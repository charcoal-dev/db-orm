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

use Charcoal\Database\ORM\Schema\Charset;

/**
 * Trait ColumnCharsetTrait
 * @package Charcoal\Database\ORM\Schema\Traits
 */
trait ColumnCharsetTrait
{
    /**
     * @param \Charcoal\Database\ORM\Schema\Charset $charset
     * @return $this
     */
    final public function charset(Charset $charset): static
    {
        $this->attributes->charset = $charset;
        return $this;
    }
}

