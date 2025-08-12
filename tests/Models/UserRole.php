<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

namespace Charcoal\Database\Tests\Orm\Models;

enum UserRole: string
{
    case USER = "user";
    case MODERATOR = "mod";
}