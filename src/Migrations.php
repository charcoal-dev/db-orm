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
use Charcoal\Database\DbDriver;
use Charcoal\Database\ORM\Schema\Columns\AbstractColumn;
use Charcoal\Database\ORM\Schema\Columns\IntegerColumn;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class Migrations
 * @package Charcoal\Database\ORM
 */
class Migrations
{
    private array $migrations = [];

    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    /**
     * @param \Charcoal\Database\ORM\AbstractOrmTable $table
     */
    public function __construct(public readonly AbstractOrmTable $table)
    {
    }

    /**
     * @param int $version
     * @param \Closure $migrationProvider
     * @return $this
     */
    public function add(int $version, \Closure $migrationProvider): static
    {
        $this->migrations[strval($version)] = $migrationProvider;
        ksort($this->migrations);
        return $this;
    }

    /**
     * @param \Charcoal\Database\Database $db
     * @param int $versionFrom
     * @param int $versionTo
     * @return array
     */
    public function getQueries(Database $db, int $versionFrom = 0, int $versionTo = 0): array
    {
        $migrations = [];
        foreach ($this->migrations as $version => $migration) {
            $version = intval($version);
            if ($version < $versionFrom) {
                continue;
            }

            if ($version > $versionTo) {
                break;
            }

            $queriesSet = call_user_func_array($migration, [$db, $this->table, $version]);
            if (!is_array($queriesSet)) {
                throw new \UnexpectedValueException(sprintf(
                    'Unexpected value of type "%s" from "%s" migrations version %d',
                    gettype($queriesSet),
                    $this->table->name,
                    $version
                ));
            }

            foreach ($queriesSet as $query) {
                if (!is_string($query)) {
                    throw new \UnexpectedValueException(sprintf(
                        'Unexpected value of type "%s" in one of the migrations for "%s" from version %d',
                        gettype($queriesSet),
                        $this->table->name,
                        $version
                    ));
                }

                $migrations[] = $query;
            }
        }

        return $migrations;
    }

    /**
     * @param \Charcoal\Database\Database $db
     * @param \Charcoal\Database\ORM\AbstractOrmTable $table
     * @param string $column
     * @param string $previous
     * @return string
     */
    public static function alterTableAddColumn(
        Database         $db,
        AbstractOrmTable $table,
        string           $column,
        string           $previous
    ): string
    {
        return "ALTER TABLE `" . $table->name . "` ADD COLUMN " .
            static::columnSpecSQL($db, $table, $table->columns->get($column)) .
            " AFTER `" . $table->columns->get($previous)->attributes->name . "`;";
    }

    /**
     * @param \Charcoal\Database\ORM\AbstractOrmTable $table
     * @return string
     */
    public static function dropTableIfExists(AbstractOrmTable $table): string
    {
        return "DROP TABLE IF EXISTS `" . $table->name . "`;";
    }

    /**
     * @param \Charcoal\Database\Database $db
     * @param \Charcoal\Database\ORM\AbstractOrmTable $table
     * @param bool $createIfNotExists
     * @param string ...$columns
     * @return array
     */
    public static function createTable(Database $db, AbstractOrmTable $table, bool $createIfNotExists, string ...$columns): array
    {
        $driver = $db->credentials->driver;
        $statement = [];
        $mysqlUniqueKeys = [];

        // Create statement
        $statement[] = $createIfNotExists ? "CREATE TABLE IF NOT EXISTS" : "CREATE TABLE";
        $statement[0] = $statement[0] . " `" . $table->name . "` (";

        foreach ($columns as $colName) {
            $column = $table->columns->get($colName);
            $columnSql = static::columnSpecSQL($db, $table, $column);

            // Unique
            if ($column->attributes->unique) {
                if ($db->credentials->driver === DbDriver::MYSQL) {
                    $mysqlUniqueKeys[] = $column->attributes->name;
                }
            }

            $statement[] = $columnSql . ",";
        }

        // MySQL Unique Keys
        if ($driver === DbDriver::MYSQL) {
            foreach ($mysqlUniqueKeys as $mysqlUniqueKey) {
                $statement[] = "UNIQUE KEY (`" . $mysqlUniqueKey . "`),";
            }
        }

        // Constraints
        /** @var \Charcoal\Database\ORM\Schema\Constraints\AbstractConstraint $constraint */
        foreach ($table->constraints as $constraint) {
            $statement[] = $constraint->getConstraintSQL($driver) . ",";
        }

        // Finishing
        $lastIndex = count($statement);
        $statement[$lastIndex - 1] = substr($statement[$lastIndex - 1], 0, -1);
        $statement[] = match ($driver) {
            DbDriver::MYSQL => sprintf(') ENGINE=%s;', $table->attributes->mysqlEngine),
            default => ");",
        };

        return $statement;
    }

    /**
     * @param \Charcoal\Database\Database $db
     * @param \Charcoal\Database\ORM\AbstractOrmTable $table
     * @param \Charcoal\Database\ORM\Schema\Columns\AbstractColumn $col
     * @return string
     */
    public static function columnSpecSQL(Database $db, AbstractOrmTable $table, AbstractColumn $col): string
    {
        $columnSql = "`" . $col->attributes->name . "` " . $col->getColumnSQL($db->credentials->driver);

        // Signed or Unsigned
        if (isset($col->attributes->unSigned)) {
            if ($col->attributes->unSigned) {
                if ($col instanceof IntegerColumn) {
                    /** @noinspection PhpStatementHasEmptyBodyInspection */
                    if ($db->credentials->driver->value === "sqlite" && $col->attributes->autoIncrement) {
                        // SQLite auto-increment columns can't be unsigned
                    } else {
                        $columnSql .= " UNSIGNED";
                    }
                } else {
                    $columnSql .= " UNSIGNED";
                }
            }
        }

        // Primary Key
        if ($col->attributes->name === $table->columns->getPrimaryKey()) {
            $columnSql .= " PRIMARY KEY";
        }

        // Auto-increment
        if ($col instanceof IntegerColumn) {
            if ($col->attributes->autoIncrement) {
                $columnSql .= match ($db->credentials->driver) {
                    DbDriver::SQLITE => " AUTOINCREMENT",
                    default => " auto_increment",
                };
            }
        }

        // Unique
        if ($col->attributes->unique) {
            if ($db->credentials->driver->value == "sqlite") {
                $columnSql .= " UNIQUE";
            }
        }

        // MySQL specific attributes
        if ($db->credentials->driver === DbDriver::MYSQL) {
            if ($col->attributes->charset) {
                $columnSql .= " CHARACTER SET " . $col->attributes->charset->value;
                $columnSql .= " COLLATE " . $col->attributes->charset->getCollation();
            }
        }

        // Nullable?
        if (!$col->attributes->nullable) {
            $columnSql .= " NOT NULL";
        }

        // Default value
        if (is_null($col->attributes->defaultValue)) {
            if ($col->attributes->nullable) {
                $columnSql .= " default NULL";
            }
        } else {
            $columnSql .= " default ";
            $columnSql .= is_string($col->attributes->defaultValue) ?
                "'" . $col->attributes->defaultValue . "'" : $col->attributes->defaultValue;
        }

        return $columnSql;
    }
}
