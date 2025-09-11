<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\MySqlEngine;
use Charcoal\Database\Orm\Schema\Builder\Columns\AbstractColumnBuilder;
use Charcoal\Database\Orm\Schema\Builder\Columns\IntegerColumn;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\TableAttributesBuilder;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Manages database migrations by tracking versioned queries and
 * generating SQL statements for schema changes.
 */
final class Migrations
{
    private array $migrations = [];

    public function __construct(
        private readonly int $versionFrom = 0,
        private readonly int $versionTo = 0
    )
    {
    }

    /**
     * @api
     */
    public function includeTable(AbstractOrmTable $table): self
    {
        $tables = $table->getMigrations($this->versionFrom, $this->versionTo);
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
     * @api
     */
    public function getVersionedQueries(): array
    {
        return $this->migrations;
    }

    /**
     * @api
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
     * Helper method to generate an ALTER TABLE statement for adding a new column.
     */
    public static function alterTableAddColumn(
        AbstractOrmTable $table,
        string           $columnId,
        string           $previousId
    ): string
    {
        $column = $table->snapshot->columns[$columnId] ?? null;
        if (!$column) {
            throw new \RuntimeException("Column \"" . $columnId . "\" not found in table");
        }

        $previous = $table->snapshot->columns[$previousId] ?? null;
        if (!$previous) {
            throw new \RuntimeException("Column \"" . $previousId . "\" not found in table");
        }

        return "ALTER TABLE " . $table->name . " ADD COLUMN " . $column->schemaSql .
            " AFTER " . $previous->name . ";";
    }

    /**
     * @api
     */
    public static function dropTableIfExists(AbstractOrmTable $table): string
    {
        return "DROP TABLE IF EXISTS " . $table->name . ";";
    }

    /**
     * Creates a table statement based on the provided parameters.
     */
    public static function createTable(
        AbstractOrmTable $table,
        bool             $createIfNotExists,
        ?StringVector    $useColumns = null
    ): array
    {
        $statement = [];
        $mysqlUniqueKeys = [];

        // Create statement
        $statement[] = $createIfNotExists ? "CREATE TABLE IF NOT EXISTS" : "CREATE TABLE";
        $statement[0] = $statement[0] . " " . $table->name . " (";

        $finalColumns = [];
        if ($useColumns) {
            foreach ($useColumns as $colName) {
                if (!isset($table->snapshot->columns[$colName])) {
                    throw new \RuntimeException("Column \"" . $colName . "\" not found in table");
                }

                $finalColumns[] = $table->snapshot->columns[$colName];
            }
        } else {
            $finalColumns = $table->snapshot->columns;
        }

        foreach ($finalColumns as $column) {
            $columnSql = $column->schemaSql;

            // Unique
            if ($column->unique) {
                if ($table->snapshot->driver === DbDriver::MYSQL) {
                    $mysqlUniqueKeys[] = $column->name;
                }
            }

            $statement[] = $columnSql . ",";
        }

        // MySQL Unique Keys
        if ($table->snapshot->driver === DbDriver::MYSQL) {
            foreach ($mysqlUniqueKeys as $mysqlUniqueKey) {
                $statement[] = "UNIQUE KEY (" . $mysqlUniqueKey . "),";
            }
        }

        // Constraints
        foreach ($table->snapshot->constraints as $constraint) {
            $statement[] = $constraint->schemaSql . ",";
        }

        // Finishing
        $lastIndex = count($statement);
        $statement[$lastIndex - 1] = substr($statement[$lastIndex - 1], 0, -1);
        $statement[] = match ($table->snapshot->driver) {
            DbDriver::MYSQL => sprintf(') ENGINE=%s;', $table->mySqlEngine?->value ?? MySqlEngine::InnoDb->value),
            default => ");",
        };

        return $statement;
    }

    /**
     * Generates the SQL string representation of a column specification based on its attributes
     * and the database driver's requirements.
     */
    public static function columnSpecSQL(
        TableAttributesBuilder $attributes,
        ColumnsBuilder         $columns,
        AbstractColumnBuilder  $column
    ): string
    {
        $columnAttributes = $column->getAttributes();
        $columnSql = $columnAttributes->name . " " . $column->getColumnSQL($attributes->driver);

        // Signed or Unsigned
        if ($columnAttributes->unSigned && $attributes->driver === DbDriver::MYSQL) {
            $columnSql .= " UNSIGNED";
        }

        // Primary Key
        $columnIsPrimary = $columnAttributes->name === $columns->getPrimaryKey();
        if ($columnIsPrimary) {
            $columnSql .= " PRIMARY KEY";
        }

        // Auto-increment
        if ($column instanceof IntegerColumn) {
            if ($columnAttributes->autoIncrement) {
                $columnSql .= match ($attributes->driver) {
                    DbDriver::SQLITE => $columnIsPrimary ? " AUTOINCREMENT" :
                        throw new \LogicException("Auto-increment not allowed for non-primary keys in SQLite"),
                    DbDriver::MYSQL => " auto_increment",
                    DbDriver::PGSQL => " GENERATED ALWAYS AS IDENTITY",
                };
            }
        }

        // Unique
        if ($columnAttributes->unique) {
            $columnSql .= match ($attributes->driver) {
                DbDriver::SQLITE,
                DbDriver::PGSQL => " UNIQUE",
                default => ""
            };
        }

        // MySQL specific attributes
        if ($attributes->driver === DbDriver::MYSQL) {
            if ($columnAttributes->charset) {
                $columnSql .= " CHARACTER SET " . match ($columnAttributes->charset) {
                        Charset::ASCII => "ascii",
                        Charset::UTF8 => "utf8mb4",
                    };
                $columnSql .= " COLLATE " . match ($columnAttributes->charset) {
                        Charset::ASCII => "ascii_general_ci",
                        Charset::UTF8 => "utf8mb4_unicode_ci",
                    };
            }
        }

        // Nullable?
        if (!$columnAttributes->nullable) {
            $columnSql .= " NOT NULL";
        }

        // Default value
        if (is_null($columnAttributes->defaultValue)) {
            if ($columnAttributes->nullable) {
                $columnSql .= " default NULL";
            }
        } else {
            $columnSql .= " default ";
            $columnSql .= is_string($columnAttributes->defaultValue) ?
                "'" . $columnAttributes->defaultValue . "'" : $columnAttributes->defaultValue;
        }

        if ($attributes->enforceChecks && $columnAttributes->enforceChecks) {
            $checkConstraintSql = $column->getCheckConstraintSQL($attributes->driver);
            if ($checkConstraintSql) {
                $columnSql .= " " . $checkConstraintSql;
            }
        }

        return $columnSql;
    }
}
