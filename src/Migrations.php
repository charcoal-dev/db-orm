<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

use Charcoal\Base\Enums\Charset;
use Charcoal\Base\Vectors\StringVector;
use Charcoal\Database\DatabaseClient;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Schema\Columns\AbstractColumn;
use Charcoal\Database\Orm\Schema\Columns\IntegerColumn;

/**
 * Class Migrations
 * @package Charcoal\Database\Orm
 */
class Migrations
{
    private array $migrations = [];

    public function __construct(
        private readonly DatabaseClient $db,
        private readonly int            $versionFrom = 0,
        private readonly int            $versionTo = 0
    )
    {
    }

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

    public function getVersionedQueries(): array
    {
        return $this->migrations;
    }

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

    public static function alterTableAddColumn(
        DatabaseClient   $db,
        AbstractOrmTable $table,
        string           $column,
        string           $previous
    ): string
    {
        return "ALTER TABLE `" . $table->name . "` ADD COLUMN " .
            static::columnSpecSQL($db, $table, $table->columns->get($column)) .
            " AFTER `" . $table->columns->get($previous)->attributes->name . "`;";
    }

    public static function dropTableIfExists(AbstractOrmTable $table): string
    {
        return "DROP TABLE IF EXISTS `" . $table->name . "`;";
    }

    public static function createTable(
        DatabaseClient   $db,
        AbstractOrmTable $table,
        bool             $createIfNotExists,
        ?StringVector    $columns = null
    ): array
    {
        $driver = $db->credentials->driver;
        $statement = [];
        $mysqlUniqueKeys = [];

        // Create statement
        $statement[] = $createIfNotExists ? "CREATE TABLE IF NOT EXISTS" : "CREATE TABLE";
        $statement[0] = $statement[0] . " `" . $table->name . "` (";

        $finalColumns = [];
        if ($columns) {
            foreach ($columns as $colName) {
                $finalColumns[] = $table->columns->get($colName);
            }
        } else {
            $finalColumns = $table->columns;
        }


        foreach ($finalColumns as $column) {
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
        /** @var \Charcoal\Database\Orm\Schema\Constraints\AbstractConstraint $constraint */
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


    public static function columnSpecSQL(DatabaseClient $db, AbstractOrmTable $table, AbstractColumn $col): string
    {
        $columnSql = "`" . $col->attributes->name . "` " . $col->getColumnSQL($db->credentials->driver);

        // Signed or Unsigned
        if (isset($col->attributes->unSigned)) {
            if ($col->attributes->unSigned) {
                if ($col instanceof IntegerColumn) {
                    $columnSql = ($db->credentials->driver === DbDriver::SQLITE && $col->attributes->autoIncrement) ?
                        "" : " UNSIGNED";
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
            if ($db->credentials->driver == DbDriver::SQLITE) {
                $columnSql .= " UNIQUE";
            }
        }

        // MySQL specific attributes
        if ($db->credentials->driver === DbDriver::MYSQL) {
            if ($col->attributes->charset) {
                $columnSql .= " CHARACTER SET " . strtolower($col->attributes->charset->value);
                $columnSql .= " COLLATE " . match ($col->attributes->charset) {
                        Charset::ASCII => "ascii_general_ci",
                        Charset::UTF8 => "utf8mb4_unicode_ci",
                    };
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
