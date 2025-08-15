<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

use Charcoal\Base\Enums\Sort;
use Charcoal\Base\Traits\NoDumpTrait;
use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Base\Vectors\StringVector;
use Charcoal\Database\DatabaseClient;
use Charcoal\Database\Enums\LockFlag;
use Charcoal\Database\Exceptions\DbQueryException;
use Charcoal\Database\Exceptions\QueryExecuteException;
use Charcoal\Database\Orm\Concerns\OrmError;
use Charcoal\Database\Orm\Exceptions\OrmQueryException;
use Charcoal\Database\Orm\Schema\Attributes;
use Charcoal\Database\Orm\Schema\Columns;
use Charcoal\Database\Orm\Schema\Constraints;
use Charcoal\Database\Orm\Schema\TableMigrations;
use Charcoal\Database\Queries\ExecutedQuery;

/**
 * Class AbstractOrmTable
 * @package Charcoal\Database\Orm
 */
abstract class AbstractOrmTable
{
    public readonly Columns $columns;
    public readonly Constraints $constraints;
    public readonly Attributes $attributes;

    protected ?DatabaseClient $dbInstance = null;
    protected ?TableMigrations $migrations = null;

    use NoDumpTrait;
    use NotCloneableTrait;

    public function __construct(public readonly string $name)
    {
        $this->columns = new Columns();
        $this->constraints = new Constraints();
        $this->attributes = new Attributes();

        $this->structure($this->columns, $this->constraints);

        $this->migrations = new TableMigrations($this);
    }

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
     */
    abstract protected function structure(Columns $cols, Constraints $constraints): void;

    /**
     * Use this method to define migrations in ascending order
     */
    abstract protected function migrations(TableMigrations $migrations): void;

    /**
     * This method should return a blank new child model/object, OR null
     */
    abstract public function newChildObject(array $row): object|null;

    /**
     * Use this method to registration all migrations defined in "migrations" method
     */
    final public function generateMigrations(): void
    {
        $this->migrations($this->migrations);
    }

    public function getMigrations(DatabaseClient $db, int $versionFrom = 0, int $versionTo = 0): array
    {
        return $this->migrations->getQueries($db, $versionFrom, $versionTo);
    }

    /**
     * @throws OrmQueryException
     */
    public function queryFind(
        string    $whereQuery = "1",
        array     $whereData = [],
        ?array    $selectColumns = null,
        ?Sort     $sort = null,
        ?string   $sortColumn = null,
        int       $offset = 0,
        int       $limit = 0,
        ?LockFlag $lock = null
    ): OrmFetchQuery
    {
        $query = $this->resolveDbInstance()
            ->queryBuilder()->table($this->name)
            ->where($this->normalizeWhereClause($whereQuery), $whereData);

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
            throw new OrmQueryException(OrmError::QUERY_EXECUTE, $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws OrmQueryException
     */
    public function queryDelete(
        string $whereQuery = "WHERE ...",
        array  $whereData = []
    ): ExecutedQuery
    {
        $stmt = "DELETE FROM `" . $this->name . "` WHERE " . $this->normalizeWhereClause($whereQuery);
        return $this->execDbQuery($stmt, $whereData);
    }

    /**
     * @throws OrmQueryException
     */
    public function queryDeletePrimaryKey(int|string $value): ExecutedQuery
    {
        return $this->queryDelete($this->whereClauseFromPrimary(null, null), [$value]);
    }

    /**
     * @throws OrmQueryException
     */
    public function queryInsert(array|object $model, bool $ignoreDuplicate = false): ExecutedQuery
    {
        $data = $this->fromObjectToArray($model);
        return $this->execDbQuery($this->buildInsertQuery($ignoreDuplicate, $data), $data);
    }

    /**
     * @throws OrmQueryException
     */
    public function querySave(
        array|object $model,
        StringVector $updateCols
    ): ExecutedQuery
    {
        $updates = [];
        foreach ($updateCols as $updateCol) {
            $column = $this->columns->search($updateCol);
            if (!$column) {
                throw new OrmQueryException(OrmError::QUERY_BUILD_ERROR,
                    'Cannot find a column in update part of query');
            }

            $updates[] = "`" . $column->attributes->name . "`=:" . $column->attributes->name;
        }

        $data = $this->fromObjectToArray($model);
        $stmt = $this->buildInsertQuery(false, $data) . " ON DUPLICATE KEY UPDATE " . implode(", ", $updates);
        return $this->execDbQuery($stmt, $data);
    }

    /**
     * @throws OrmQueryException
     */
    public function queryUpdate(
        array      $changes,
        int|string $primaryValue,
        ?string    $primaryColumn = null,
    ): ExecutedQuery
    {
        $updateQuery = $this->buildUpdateQueryParts($changes);
        $updateBind = $updateQuery[1];
        $updateBind["update_Primary_Key"] = $primaryValue;
        $stmt = "UPDATE `" . $this->name . "` SET " . implode(", ", $updateQuery[0]) . " " .
            $this->whereClauseFromPrimary($primaryColumn, "update_Primary_Key");
        return $this->execDbQuery($stmt, $updateBind);
    }

    /**
     * @throws OrmQueryException
     */
    protected function resolveDbInstance(): DatabaseClient
    {
        if ($this->dbInstance) {
            return $this->dbInstance;
        }

        $this->dbInstance = OrmDbResolver::getDbInstance(static::class);
        if (!$this->dbInstance) {
            throw new OrmQueryException(OrmError::DB_RESOLVE_FAIL);
        }

        return $this->dbInstance;
    }

    /**
     * @param array $changes
     * @return array
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     */
    private function buildUpdateQueryParts(array $changes): array
    {
        $updateParams = [];
        $updateBind = [];
        foreach ($changes as $key => $value) {
            $column = $this->columns->search($key);
            if (!$column) {
                throw new OrmQueryException(OrmError::QUERY_BUILD_ERROR, "Cannot find a column in changes array");
            }

            $updateParams[] = "`" . $column->attributes->name . "`=:" . $column->attributes->name;
            $updateBind[$column->attributes->name] = $column->attributes->resolveValueForDb($value, $column);
        }

        if (!$updateBind) {
            throw new OrmQueryException(OrmError::NO_CHANGES, "There are no changes");
        }

        return [$updateParams, $updateBind];
    }

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
            /** @var \Charcoal\Database\Orm\Schema\Columns\AbstractColumn $column */
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

    private function normalizeWhereClause(string $whereClause): string
    {
        $whereClause = trim($whereClause);
        if (str_starts_with($whereClause, "WHERE")) {
            return substr($whereClause, 6);
        }

        return $whereClause;
    }

    /**
     * @throws OrmQueryException
     */
    private function whereClauseFromPrimary(?string $primaryColumn = null, ?string $bindAssocParam = null): string
    {
        $primaryColumnId = $primaryColumn ?? $this->columns->getPrimaryKey();
        if (!$primaryColumnId) {
            throw new OrmQueryException(OrmError::NO_PRIMARY_COLUMN);
        }

        $primaryColumn = $this->columns->search($primaryColumnId);
        if (!$primaryColumn) {
            throw new OrmQueryException(OrmError::NO_PRIMARY_COLUMN);
        }

        return "WHERE `" . $primaryColumn->attributes->name . "`=" . ($bindAssocParam ? ":" . $bindAssocParam : "?");
    }

    /**
     * @throws OrmQueryException
     */
    private function execDbQuery(string $query, array $data = []): ExecutedQuery
    {
        try {
            return $this->resolveDbInstance()->exec($query, $data);
        } catch (QueryExecuteException $e) {
            throw new OrmQueryException(OrmError::QUERY_EXECUTE, $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws OrmQueryException
     */
    private function fromObjectToArray(array|object $model): array
    {
        if (is_array($model)) {
            return $model;
        }

        $data = [];
        /** @var \Charcoal\Database\Orm\Schema\Columns\AbstractColumn $column */
        foreach ($this->columns as $column) {
            $prop = $column->attributes->modelMapKey;
            if (!property_exists($model, $prop) || !isset($model->$prop)) {
                if ($column->attributes->nullable) {
                    $data[$column->attributes->name] = null;
                }

                continue;
            }

            $data[$column->attributes->name] = $column->attributes->resolveValueForDb($model->$prop, $column);
        }

        return $data;
    }
}
