<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

use Charcoal\Base\Support\ObjectHelper;
use Charcoal\Database\Database;

/**
 * Class OrmDbResolver
 * @package Charcoal\Database\Orm
 */
class OrmDbResolver
{
    /** @var array<string,Database> $tableClasses */
    private static array $tableClasses = [];
    /** @var array<string,array<string,string>> $tableClasses */
    private static array $dbTables = [];

    public static function Bind(Database $db, AbstractOrmTable|string $tableClass): void
    {
        if (is_string($tableClass)) {
            if (!ObjectHelper::isValidClass($tableClass) || !is_subclass_of($tableClass, AbstractOrmTable::class, true)) {
                throw new \InvalidArgumentException('Cannot bind DB instance to invalid class');
            }
        }

        if ($tableClass instanceof AbstractOrmTable) {
            $tableClass = $tableClass::class;
        }

        static::$tableClasses[$tableClass] = $db;
        static::$dbTables[$db->credentials->dbName][] = $tableClass;
    }

    public static function getDbInstance(string $tableClass): ?Database
    {
        return static::$tableClasses[$tableClass] ?? null;
    }

    public static function getTables(Database $db): array
    {
        return static::$dbTables[$db->credentials->dbName] ?? [];
    }
}

