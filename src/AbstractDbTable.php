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
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\Migrations;
use Charcoal\Database\Queries\DbExecutedQuery;
use Charcoal\Database\Queries\SortFlag;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;

/**
 * Class AbstractDbTable
 * @package Charcoal\Database\ORM
 */
abstract class AbstractDbTable
{
    /** @var string Table name */
    public const TABLE = null;

    /** @var string */
    public readonly string $name;
    /** @var Columns */
    public readonly Columns $columns;
    /** @var Constraints */
    public readonly Constraints $constraints;

    protected ?Database $dbInstance = null;
    protected ?Migrations $migrations = null;

    use NoDumpTrait;
    use NotCloneableTrait;

    /**
     * AbstractDbTable constructor.
     */
    final public function __construct()
    {
        $this->columns = new Columns();
        $this->constraints = new Constraints();

        // Get table names and engine
        $this->name = static::TABLE;
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!is_string($this->name) || !$this->name) {
            throw new \InvalidArgumentException(sprintf('Invalid TABLE const for table "%s"', static::class));
        }

        // On Construct Callback
        $this->onConstruct();

        // Callback schema method for table structure
        $this->structure($this->columns, $this->constraints);

        // Callback schema method to set all migrations
        $this->migrations = new Migrations($this);
        $this->migrations($this->migrations);
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
        $this->dbInstance = null;

        // Re-run method that sets all migrations callbacks
        $this->migrations = new Migrations($this);
        $this->migrations($this->migrations);
    }

    /**
     * Callback method triggered directly after constructor is called
     * @return void
     */
    abstract protected function onConstruct(): void;

    /**
     * Create table schema in this method using $cols and $constraints
     * @param Columns $cols
     * @param Constraints $constraints
     */
    abstract public function structure(Columns $cols, Constraints $constraints): void;

    /**
     * Use this method to define migrations in ascending order
     * @param \Charcoal\Database\ORM\Schema\Migrations $migrations
     * @return void
     */
    abstract public function migrations(Migrations $migrations): void;

    /**
     * This method should return a blank new model object, OR null
     * @return object|null
     */
    abstract public function newModelObject(): object|null;

    /**
     * @param \Charcoal\Database\Database $db
     * @return array
     */
    public function getMigrations(Database $db): array
    {
        return $this->migrations->getAll($db);
    }

    /**
     * @param string $whereQuery
     * @param array|null $whereData
     * @param array|null $selectColumns
     * @param \Charcoal\Database\Queries\SortFlag|null $sort
     * @param string|null $sortColumn
     * @param int $offset
     * @param int $limit
     * @param bool $lock
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\ORM\OrmModelMapper
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    protected function queryFind(
        string    $whereQuery = "1",
        array     $whereData = null,
        ?array    $selectColumns = null,
        ?SortFlag $sort = null,
        ?string   $sortColumn = null,
        int       $offset = 0,
        int       $limit = 0,
        bool      $lock = false,
        ?Database $db = null
    ): OrmModelMapper
    {
        $query = $this->resolveDbInstance($db)
            ->queryBuilder()->table($this->name)->where($whereQuery, $whereData);
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
            $query->lock();
        }

        try {
            return new OrmModelMapper($query->fetch(), $this);
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
    protected function queryDelete(
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
    protected function queryDeletePrimaryKey(int|string $value): DbExecutedQuery
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
    protected function queryInsert(array|object $model, bool $ignoreDuplicate = false, ?Database $db = null): DbExecutedQuery
    {
        return $this->execDbQuery($this->buildInsertQuery($ignoreDuplicate), $this->dissolveModelObject($model), $db);
    }

    /**
     * @param array|object $model
     * @param array $updates
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    protected function querySave(array|object $model, array $updates, ?Database $db = null): DbExecutedQuery
    {
        $dataSet = array_merge($this->dissolveModelObject($model), $updates);
        $updates = $this->buildUpdateQueryParts($dataSet);
        $stmt = $this->buildInsertQuery(false) . " ON DUPLICATE KEY UPDATE " . implode(", ", $updates[0]);
        return $this->execDbQuery($stmt, $dataSet, $db);
    }

    /**
     * @param array $changes
     * @param int|string $primaryValue
     * @param string|null $primaryColumn
     * @param \Charcoal\Database\Database|null $db
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    protected function queryUpdate(
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
            $updateBind[$column->attributes->name] = $column->attributes->getDissolvedModelProperty($value);
        }

        if (!$updateBind) {
            throw new OrmQueryException(OrmQueryError::NO_CHANGES, "There are no changes");
        }

        return [$updateParams, $updateBind];
    }

    /**
     * @param bool $ignoreDuplicate
     * @return string
     */
    private function buildInsertQuery(bool $ignoreDuplicate = false): string
    {
        $insertColumns = [];
        $insertParams = [];
        /** @var \Charcoal\Database\ORM\Schema\Columns\AbstractColumn $column */
        foreach ($this->columns as $column) {
            $insertColumns[] = "`" . $column->attributes->name . "`";
            $insertParams[] = ":" . $column->attributes->name;
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
            return substr($whereClause, 0, 6);
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

            $data[$column->attributes->name] = $column->attributes->getDissolvedModelProperty($model->$prop);
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
