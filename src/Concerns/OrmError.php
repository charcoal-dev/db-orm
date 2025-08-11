<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Exception;

/**
 * Class OrmError
 * @package Charcoal\Database\Orm\Exception
 */
enum OrmError: int
{
    case DB_RESOLVE_FAIL = 0x64;
    case QUERY_EXECUTE_EX = 0xc8;
    case QUERY_FETCH_EX = 0x12c;
    case NO_PRIMARY_COLUMN = 0x190;
    case NO_CHANGES = 0x1f4;
    case QUERY_BUILD_ERROR = 0x258;
    case COL_VALUE_TYPE_ERROR = 0x2bc;
}

