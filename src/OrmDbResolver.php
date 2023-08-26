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

namespace Charcoal\Database\ORM;

use Charcoal\Database\Database;
use Charcoal\OOP\OOP;

/**
 * Class OrmDbResolver
 * @package Charcoal\Database\ORM
 */
class OrmDbResolver
{
    private static array $tableClasses = [];

    /**
     * @param \Charcoal\Database\Database $db
     * @param string $tableClass
     * @return void
     */
    public static function Bind(Database $db, string $tableClass): void
    {
        if (!OOP::isValidClass($tableClass) || !is_subclass_of(AbstractDbTable::class, $tableClass)) {
            throw new \InvalidArgumentException('Cannot bind DB instance to invalid class');
        }

        static::$tableClasses[$tableClass] = $db;
    }

    /**
     * @param string $tableClass
     * @return \Charcoal\Database\Database|null
     */
    public static function getDbInstance(string $tableClass): ?Database
    {
        return static::$tableClasses[$tableClass] ?? null;
    }
}

