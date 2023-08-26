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

namespace Charcoal\Database\ORM\Exception;

/**
 * Class OrmQueryError
 * @package Charcoal\Database\ORM\Exception
 */
enum OrmQueryError: int
{
    case DB_RESOLVE_FAIL = 0x64;
    case QUERY_EXECUTE_EX = 0xc8;
    case QUERY_FETCH_EX = 0x12c;
    case NO_PRIMARY_COLUMN = 0x190;
    case NO_CHANGES = 0x1f4;
    case QUERY_BUILD_ERROR = 0x258;
}

