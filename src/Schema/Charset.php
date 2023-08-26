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

namespace Charcoal\Database\ORM\Schema;

/**
 * Class Charset
 * @package Charcoal\Database\ORM\Schema
 */
enum Charset: string
{
    case ASCII = "ascii";
    case UTF8MB4 = "utf8mb4";

    /**
     * @return string
     */
    public function getCollation(): string
    {
        return match ($this) {
            Charset::ASCII => "ascii_general_ci",
            Charset::UTF8MB4 => "utf8mb4_unicode_ci"
        };
    }
}