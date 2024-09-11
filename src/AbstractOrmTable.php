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
use Charcoal\Database\Exception\DbQueryException;
use Charcoal\Database\Exception\QueryExecuteException;
use Charcoal\Database\ORM\Exception\OrmQueryError;
use Charcoal\Database\ORM\Exception\OrmQueryException;
use Charcoal\Database\ORM\Schema\Attributes;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;
use Charcoal\Database\Queries\DbExecutedQuery;
use Charcoal\Database\Queries\LockFlag;
use Charcoal\Database\Queries\SortFlag;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Vectors\StringVector;

/**
 * Class AbstractOrmTable
 * @package Charcoal\Database\ORM
 */
abstract class AbstractOrmTable
{
    public readonly Columns $columns;
    public readonly Constraints $constraints;
    public readonly Attributes $attributes;

    protected ?Database $dbInstance = null;
    protected ?TableMigrations $migrations = null;

    use NoDumpTrait;
    use NotCloneableTrait;

    /**
     * @param string $name
     */
    public function __construct(public readonly string $name)
    {
        $this->columns = new Columns();
        $this->constraints = new Constraints();
        $this->attributes = new Attributes();

        // Callback schema method for table structure
        $this->structure($this->columns, $this->constraints);

        // Callback schema method to set all migrations
        $this->migrations = new TableMigrations($this);
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            "name" => $this->name,
            "columns" => $this->columns,
            "constraints" => $this->constraints,
            "attributes" => $this->attributes,
            "dbInstance" => null,
            "migrations" => null,
        ];
    }

    /**
     * @param array $object
     * @return void
     */
    public function __unserialize(array $object): void
    {
        $this->name = $object["name"];
        $this->columns = $object["columns"];
        $this->constraints = $object["constraints"];
        $this->attributes = $object["attributes"];
        $this->dbInstance = null;
        $this->migrations = new TableMigrations($this);
    }

    /**
     * Create table schema in this method using $cols and $constraints
     * @param Columns $cols
     * @param Constraints $constraints
     */
    abstract protected function structure(Columns $cols, Constraints $constraints): void;

    /**
     * Use this method to define migrations in ascending order
     * @param \Charcoal\Database\ORM\Schema\TableMigrations $migrations
     * @return void
     */
    abstract protected function migrations(TableMigrations $migrations): void;

    /**
     * This method should return a blank new child model/object, OR null
     * @param array $row
     * @return object|null
     */
    abstract public function newChildObject(array $row): object|null;

    /**
     * Use this method to registration all migrations defined in "migrations" method
     * @return void
     */
    final public function generateMigrations(): void
    {
        $this->migrations($this->migrations);
    }

    /**
     * @param \Charcoal\Database\Database $db
     * @param int $versionFrom
     * @param int $versionTo
     * @return array
     */
    public function getMigrations(Database $db, int $versionFrom = 0, int $versionTo = 0): array
    {
        return $this->migrations->getQueries($db, $versionFrom, $versionTo);
    }

    /**
     * @param string $whereQuery
     * @param array $whereData
     * @param array|null $selectColumns
     * @param \Charcoal\Database\Queries\SortFlag|null $sort
     * @param string|null $sortColumn
     * @param int $offset
     * @param int $limit
     * @param \Charcoal\Database\Queries\LockFlag|null $lock
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\ORM\OrmFetchQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function queryFind(
        string    $whereQuery = "1",
        array     $whereData = [],
        ?array    $selectColumns = null,
        ?SortFlag $sort = null,
        ?string   $sortColumn = null,
        int       $offset = 0,
        int       $limit = 0,
        ?LockFlag $lock = null,
        ?Database $db = null
    ): OrmFetchQuery
    {
        $query = $this->resolveDbInstance($db)
            ->queryBuilder()->table($this->name)->where($this->normalizeWhereClause($whereQuery), $whereData);
        if ($selectColumns) {
            $query->cols(...$selectColumns);
        }

        if ($offset > 0) {
            $query->start($offset);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        if ($sort && $sortColumn) {
            $query->sort($sort, $sortColumn);
        }

        if ($lock) {
            $query->lock($lock);
        }

        try {
            return new OrmFetchQuery($query->fetch(), $this);
        } catch (DbQueryException $e) {
            throw new OrmQueryException(OrmQueryError::QUERY_EXECUTE_EX, $e->getMessage(), previous: $e);
        }
    }

    /**
     * @param string $whereQuery
     * @param array $whereData
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function queryDelete(
        string    $whereQuery = "WHERE ...",
        array     $whereData = [],
        ?Database $db = null
    ): DbExecutedQuery
    {
        $stmt = "DELETE FROM `" . $this->name . "` WHERE " . $this->normalizeWhereClause($whereQuery);
        return $this->execDbQuery($stmt, $whereData, $db);
    }

    /**
     * @param int|string $value
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function queryDeletePrimaryKey(int|string $value): DbExecutedQuery
    {
        return $this->queryDelete($this->whereClauseFromPrimary(null, null), [$value]);
    }

    /**
     * @param array|object $model
     * @param bool $ignoreDuplicate
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function queryInsert(array|object $model, bool $ignoreDuplicate = false, ?Database $db = null): DbExecutedQuery
    {
        $data = $this->dissolveModelObject($model);
        return $this->execDbQuery($this->buildInsertQuery($ignoreDuplicate, $data), $data, $db);
    }

    /**
     * @param array|object $model
     * @param \Charcoal\OOP\Vectors\StringVector $updateCols
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function querySave(array|object $model, StringVector $updateCols, ?Database $db = null): DbExecutedQuery
    {
        $updates = [];
        foreach ($updateCols as $updateCol) {
            $column = $this->columns->search($updateCol);
            if (!$column) {
                throw new OrmQueryException(OrmQueryError::QUERY_BUILD_ERROR, 'Cannot find a column in update part of query');
            }

            $updates[] = "`" . $column->attributes->name . "`=:" . $column->attributes->name;
        }

        $data = $this->dissolveModelObject($model);
        $stmt = $this->buildInsertQuery(false, $data) . " ON DUPLICATE KEY UPDATE " . implode(", ", $updates);
        return $this->execDbQuery($stmt, $data, $db);
    }

    /**
     * @param array $changes
     * @param int|string $primaryValue
     * @param string|null $primaryColumn
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function queryUpdate(
        array      $changes,
        int|string $primaryValue,
        ?string    $primaryColumn = null,
        ?Database  $db = null
    ): DbExecutedQuery
    {
        $updateQuery = $this->buildUpdateQueryParts($changes);
        $updateBind = $updateQuery[1];
        $updateBind["update_Primary_Key"] = $primaryValue;
        $stmt = "UPDATE `" . $this->name . "` SET " . implode(", ", $updateQuery[0]) . " " .
            $this->whereClauseFromPrimary($primaryColumn, "update_Primary_Key");
        return $this->execDbQuery($stmt, $updateBind, $db);
    }

    /**
     * @param array $changes
     * @return array
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    private function buildUpdateQueryParts(array $changes): array
    {
        $updateParams = [];
        $updateBind = [];
        foreach ($changes as $key => $value) {
            $column = $this->columns->search($key);
            if (!$column) {
                throw new OrmQueryException(OrmQueryError::QUERY_BUILD_ERROR, "Cannot find a column in changes array");
            }

            $updateParams[] = "`" . $column->attributes->name . "`=:" . $column->attributes->name;
            $updateBind[$column->attributes->name] = $column->attributes->getDissolvedModelProperty($value, $column);
        }

        if (!$updateBind) {
            throw new OrmQueryException(OrmQueryError::NO_CHANGES, "There are no changes");
        }

        return [$updateParams, $updateBind];
    }

    /**
     * @param bool $ignoreDuplicate
     * @param array|null $data
     * @return string
     */
    private function buildInsertQuery(bool $ignoreDuplicate = false, ?array $data = null): string
    {
        $insertColumns = [];
        $insertParams = [];
        if ($data) {
            foreach ($data as $columnId => $value) {
                $insertColumns[] = "`" . $columnId . "`";
                $insertParams[] = ":" . $columnId;
            }
        } else {
            /** @var \Charcoal\Database\ORM\Schema\Columns\AbstractColumn $column */
            foreach ($this->columns as $column) {
                $insertColumns[] = "`" . $column->attributes->name . "`";
                $insertParams[] = ":" . $column->attributes->name;
            }
        }

        return sprintf(
            'INSERT%s INTO `%s` (%s) VALUES (%s)',
            $ignoreDuplicate ? " IGNORE" : "",
            $this->name,
            implode(", ", $insertColumns),
            implode(", ", $insertParams)
        );
    }

    /**
     * @param string $whereClause
     * @return string
     */
    private function normalizeWhereClause(string $whereClause): string
    {
        $whereClause = trim($whereClause);
        if (str_starts_with($whereClause, "WHERE")) {
            return substr($whereClause, 6);
        }

        return $whereClause;
    }

    /**
     * @param string|null $primaryColumn
     * @param string|null $bindAssocParam
     * @return string
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    private function whereClauseFromPrimary(?string $primaryColumn = null, ?string $bindAssocParam = null): string
    {
        $primaryColumnId = $primaryColumn ?? $this->columns->getPrimaryKey();
        if (!$primaryColumnId) {
            throw new OrmQueryException(OrmQueryError::NO_PRIMARY_COLUMN);
        }

        $primaryColumn = $this->columns->search($primaryColumnId);
        if (!$primaryColumn) {
            throw new OrmQueryException(OrmQueryError::NO_PRIMARY_COLUMN);
        }

        return "WHERE `" . $primaryColumn->attributes->name . "`=" . ($bindAssocParam ? ":" . $bindAssocParam : "?");
    }

    /**
     * @param string $query
     * @param array $data
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    private function execDbQuery(
        string    $query,
        array     $data = [],
        ?Database $db = null
    ): DbExecutedQuery
    {
        try {
            return $this->resolveDbInstance($db)->exec($query, $data);
        } catch (QueryExecuteException $e) {
            throw new OrmQueryException(OrmQueryError::QUERY_EXECUTE_EX, $e->getMessage(), previous: $e);
        }
    }

    /**
     * @param array|object $model
     * @return array
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    private function dissolveModelObject(array|object $model): array
    {
        if (is_array($model)) {
            return $model;
        }

        $data = [];
        /** @var \Charcoal\Database\ORM\Schema\Columns\AbstractColumn $column */
        foreach ($this->columns as $column) {
            $prop = $column->attributes->modelProperty;
            if (!property_exists($model, $prop) || !isset($model->$prop)) {
                if ($column->attributes->nullable) {
                    $data[$column->attributes->name] = null;
                }

                continue;
            }

            $data[$column->attributes->name] = $column->attributes->getDissolvedModelProperty($model->$prop, $column);
        }

        return $data;
    }

    /**
     * Resolves Database instance to be used for queries,
     * If an instance has been passed by argument, it takes priority otherwise the DB instance set in "dbInstance"
     * property of running instance, or resolves from OrmDbResolver class.
     * @param \Charcoal\Database\Database|null $dbArg
     * @return \Charcoal\Database\Database
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    protected function resolveDbInstance(?Database $dbArg = null): Database
    {
        if ($dbArg) {
            return $dbArg;
        }

        if ($this->dbInstance) {
            return $this->dbInstance;
        }

        $this->dbInstance = OrmDbResolver::getDbInstance(static::class);
        if (!$this->dbInstance) {
            throw new OrmQueryException(OrmQueryError::DB_RESOLVE_FAIL);
        }

        return $this->dbInstance;
    }
}
