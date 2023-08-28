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

namespace Charcoal\Tests\ORM;

/**
 * Class TestEnum
 * @package Charcoal\Tests\ORM
 */
enum TestEnum: string
{
    case CASE1 = "case_a1";
    case CASE2 = "case_b2";
    case CASE3 = "case_c3";
}