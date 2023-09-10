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
use Charcoal\OOP\Vectors\StringVector;

/**
 * Class Migrations
 * @package Charcoal\Database\ORM
 */
class Migrations
{
    private array $migrations = [];

    /**
     * @param \Charcoal\Database\Database $db
     * @param int $versionFrom
     * @param int $versionTo
     */
    public function __construct(
        private readonly Database $db,
        private readonly int      $versionFrom = 0,
        private readonly int      $versionTo = 0
    )
    {
    }

    /**
     * @param \Charcoal\Database\ORM\AbstractOrmTable $table
     * @return $this
     */
    public function includeTable(AbstractOrmTable $table): static
    {
        $tables = $table->getMigrations($this->db, $this->versionFrom, $this->versionTo);
        foreach ($tables as $version => $queries) {
            if (!isset($this->migrations[$version])) {
                $this->migrations[$version] = [];
            }

            foreach ($queries as $query) {
                $this->migrations[$version][] = $query;
            }
        }

        ksort($this->migrations);
        return $this;
    }

    /**
     * @return array
     */
    public function getVersionedQueries(): array
    {
        return $this->migrations;
    }

    /**
     * @return array
     */
    public function getQueries(): array
    {
        $queries = [];
        foreach ($this->migrations as $querySet) {
            foreach ($querySet as $query) {
                $queries[] = $query;
            }
        }

        return $queries;
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
     * @param \Charcoal\OOP\Vectors\StringVector $columns
     * @return array
     */
    public static function createTable(Database $db, AbstractOrmTable $table, bool $createIfNotExists, StringVector $columns): array
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
