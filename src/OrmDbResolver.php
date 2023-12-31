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
    private static array $dbTables = [];

    /**
     * @param \Charcoal\Database\Database $db
     * @param string $tableClass
     * @return void
     */
    public static function Bind(Database $db, string $tableClass): void
    {
        if (!OOP::isValidClass($tableClass) || !is_subclass_of(AbstractOrmTable::class, $tableClass)) {
            throw new \InvalidArgumentException('Cannot bind DB instance to invalid class');
        }

        static::$tableClasses[$tableClass] = $db;
        static::$dbTables[$db->credentials->dbName][] = $tableClass;
    }

    /**
     * Returns instance of Database bound with argument table class, OR NULL
     * @param string $tableClass
     * @return \Charcoal\Database\Database|null
     */
    public static function getDbInstance(string $tableClass): ?Database
    {
        return static::$tableClasses[$tableClass] ?? null;
    }

    /**
     * Returns list of all table classes names as indexed array bound with DB instance
     * @param \Charcoal\Database\Database $db
     * @return array
     */
    public static function getTables(Database $db): array
    {
        return static::$dbTables[$db->credentials->dbName] ?? [];
    }
}

