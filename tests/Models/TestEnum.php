<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm\Models;

enum TestEnum: string
{
    case CASE1 = "case_a1";
    case CASE2 = "case_b2";
    case CASE3 = "case_c3";
}