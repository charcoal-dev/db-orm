<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Database\DatabaseClient;

/**
 * This class provides functionality to manage the association between database instances
 * and ORM table classes. It allows binding a database instance to a specific table class
 * and retrieving the database or table information as needed.
 */
final class OrmDbResolver
{
    /** @var array<string,DatabaseClient> $tableClasses */
    private static array $tableClasses = [];
    /** @var array<string,array<string,string>> $tableClasses */
    private static array $dbTables = [];

    /**
     * Binds a database instance to a table class.
     */
    public static function Bind(DatabaseClient $db, AbstractOrmTable|string $tableClass): void
    {
        if (is_string($tableClass)) {
            if (!ObjectHelper::isValidClass($tableClass) ||
                !is_subclass_of($tableClass, AbstractOrmTable::class, true)) {
                throw new \InvalidArgumentException('Cannot bind DB instance to invalid class');
            }
        }

        if ($tableClass instanceof AbstractOrmTable) {
            $tableClass = $tableClass::class;
        }

        self::$tableClasses[$tableClass] = $db;
        self::$dbTables[$db->credentials->dbName][] = $tableClass;
    }

    /**
     * @api Resolves the database instance for the given table class.
     */
    public static function getDbInstance(string $tableClass): ?DatabaseClient
    {
        return self::$tableClasses[$tableClass] ?? null;
    }

    /**
     * @api Gets the list of tables bound to the given database instance.
     */
    public static function getTables(DatabaseClient $db): array
    {
        return self::$dbTables[$db->credentials->dbName] ?? [];
    }
}

