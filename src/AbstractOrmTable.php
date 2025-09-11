<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

use Charcoal\Base\Objects\Traits\ControlledSerializableTrait;
use Charcoal\Base\Objects\Traits\NoDumpTrait;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Contracts\Dataset\Sort;
use Charcoal\Database\DatabaseClient;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Enums\LockFlag;
use Charcoal\Database\Exceptions\DbQueryException;
use Charcoal\Database\Exceptions\QueryExecuteException;
use Charcoal\Database\Orm\Enums\OrmError;
use Charcoal\Database\Orm\Exceptions\OrmQueryException;
use Charcoal\Database\Orm\Schema\Builder\TableAttributesBuilder;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;
use Charcoal\Database\Orm\Schema\Snapshot\TableSnapshot;
use Charcoal\Database\Orm\Schema\TableMigrations;
use Charcoal\Database\Queries\ExecutedQuery;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Abstract class representing an Object-Relational Mapping (ORM) table.
 * This class serves as a base implementation for defining table schema, migrations,
 * and database CRUD operations within a custom ORM layer.
 */
abstract class AbstractOrmTable
{
    use NoDumpTrait;
    use NotCloneableTrait;
    use ControlledSerializableTrait;

    public readonly TableSnapshot $snapshot;
    protected ?DatabaseClient $dbInstance = null;
    protected ?TableMigrations $migrations = null;

    public function __construct(public readonly string $name, DbDriver $driver)
    {
        if (!$this->name || !preg_match(CharcoalOrm::NAME_REGEXP, $this->name)) {
            throw new \InvalidArgumentException(sprintf('Table name "%s" is invalid', $this->name));
        } elseif (CharcoalOrm::isReserved($this->name)) {
            throw new \InvalidArgumentException(sprintf('Table name "%s" is reserved', $this->name));
        }

        $columns = new ColumnsBuilder();
        $constraints = new ConstraintsBuilder();
        $attributes = new TableAttributesBuilder($this->name, $driver);

        if (method_exists($this, "setAttributes")) {
            $this->setAttributes($attributes);
        }

        $this->structure($columns, $constraints);
        $constraintSnapshots = $constraints->snapshot($this, $columns, $driver);
        $columnSnapshots = [];
        foreach ($columns as $column) {
            $columnSnapshot = $column->snapshot(Migrations::columnSpecSQL($attributes, $columns, $column));
            $columnSnapshots[$column->name] = $columnSnapshot;
        }

        $this->snapshot = new TableSnapshot(
            $columnSnapshots,
            $constraintSnapshots,
            $columns->getPrimaryKey(),
            $driver,
            $attributes->mysqlEngine
        );

        $this->migrations = new TableMigrations($this->name, $this->snapshot);
    }

    /**
     * Serialize ORM table
     */
    protected function collectSerializableData(): array
    {
        return [
            "name" => $this->name,
            "snapshot" => $this->snapshot,
            "dbInstance" => null,
            "migrations" => null,
        ];
    }

    /**
     * Unserialize ORM table
     */
    public function __unserialize(array $object): void
    {
        $this->name = $object["name"];
        $this->snapshot = $object["snapshot"];
        $this->dbInstance = null;
        $this->migrations = new TableMigrations($this->name, $this->snapshot);
    }

    /**
     * Create table schema in this method using $cols and $constraints
     */
    abstract protected function structure(
        ColumnsBuilder     $cols,
        ConstraintsBuilder $constraints
    ): void;

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
     * @api
     */
    final public function generateMigrations(): void
    {
        $this->migrations($this->migrations);
    }

    /**
     * Gets migration queries for a given version
     */
    public function getMigrations(int $versionFrom = 0, int $versionTo = 0): array
    {
        return $this->migrations->getQueries($versionFrom, $versionTo);
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
        $stmt = "DELETE FROM " . $this->name . " WHERE " . $this->normalizeWhereClause($whereQuery);
        return $this->execDbQuery($stmt, $whereData);
    }

    /**
     * @throws OrmQueryException
     * @api
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
        foreach ($updateCols as $colId) {
            $updateCol = $this->snapshot->findColumn($colId);
            if (!$updateCol) {
                throw new OrmQueryException(OrmError::QUERY_BUILD_ERROR,
                    "Cannot find a column in update part of query: " . $colId);
            }

            $column = $this->snapshot->columns[$updateCol->name];
            $updates[] = $column->name . "=:" . $column->name;
        }

        $data = $this->fromObjectToArray($model);
        $stmt = $this->buildInsertQuery(false, $data) . " ON DUPLICATE KEY UPDATE " . implode(", ", $updates);
        return $this->execDbQuery($stmt, $data);
    }

    /**
     * @throws OrmQueryException
     * @api Execute UPDATE query with primary key
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
        $stmt = "UPDATE " . $this->name . " SET " . implode(", ", $updateQuery[0]) . " " .
            $this->whereClauseFromPrimary($primaryColumn, "update_Primary_Key");
        return $this->execDbQuery($stmt, $updateBind);
    }

    /**
     * Resolves the database instance for this table
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
     * Builds UPDATE query parts
     * @throws OrmQueryException
     */
    private function buildUpdateQueryParts(array $changes): array
    {
        $updateParams = [];
        $updateBind = [];
        foreach ($changes as $key => $value) {
            $column = $this->snapshot->findColumn($key);
            if (!$column) {
                throw new OrmQueryException(OrmError::QUERY_BUILD_ERROR, "Cannot find a column in changes array");
            }

            $updateParams[] = $column->name . "=:" . $column->name;
            $updateBind[$column->name] = $this->pipeColumnValue($value, $column);
        }

        if (!$updateBind) {
            throw new OrmQueryException(OrmError::NO_CHANGES, "There are no changes");
        }

        return [$updateParams, $updateBind];
    }

    /**
     * Prepare INSERT query
     */
    private function buildInsertQuery(bool $ignoreDuplicate = false, ?array $data = null): string
    {
        $insertColumns = [];
        $insertParams = [];
        if ($data) {
            foreach ($data as $columnId => $value) {
                $insertColumns[] = $columnId;
                $insertParams[] = ":" . $columnId;
            }
        } else {
            foreach ($this->snapshot->columns as $column) {
                $insertColumns[] = $column->name;
                $insertParams[] = ":" . $column->name;
            }
        }

        return sprintf(
            'INSERT%s INTO %s (%s) VALUES (%s)',
            $ignoreDuplicate ? " IGNORE" : "",
            $this->name,
            implode(", ", $insertColumns),
            implode(", ", $insertParams)
        );
    }

    /**
     * Normalizes the WHERE clause
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
     * Creates the WHERE clause from the primary key of the table
     * @throws OrmQueryException
     */
    private function whereClauseFromPrimary(?string $primaryColumn = null, ?string $bindAssocParam = null): string
    {
        $primaryColumnId = $primaryColumn ?? $this->snapshot->primaryKey;
        if (!$primaryColumnId) {
            throw new OrmQueryException(OrmError::NO_PRIMARY_COLUMN);
        }

        $primaryColumn = $this->snapshot->findColumn($primaryColumnId);
        if (!$primaryColumn) {
            throw new OrmQueryException(OrmError::NO_PRIMARY_COLUMN);
        }

        return "WHERE " . $primaryColumn->name . "=" . ($bindAssocParam ? ":" . $bindAssocParam : "?");
    }

    /**
     * Executes a query and returns the result
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
     * Converts an entity object to an array of values that can be used to insert into a database
     * @throws OrmQueryException
     */
    private function fromObjectToArray(array|object $model): array
    {
        if (is_array($model)) {
            return $model;
        }

        $data = [];
        foreach ($this->snapshot->columns as $column) {
            $prop = $column->entityMapKey;
            if (!property_exists($model, $prop) || !isset($model->$prop)) {
                if ($column->nullable) {
                    $data[$column->name] = null;
                }

                continue;
            }

            $data[$column->name] = $this->pipeColumnValue($model->$prop, $column);
        }

        return $data;
    }

    /**
     * Pipes the value set in an entity object to be stored in a database
     * @throws OrmQueryException
     */
    private function pipeColumnValue(mixed $value, ColumnSnapshot $column): mixed
    {
        if ($column->valuePipe) {
            $value = $column->valuePipe->forDb($value, $column);
        }

        if (is_null($value)) {
            if (!$column->nullable) {
                throw new OrmQueryException(OrmError::VALUE_TYPE_ERROR,
                    sprintf('Column "%s" is not nullable', $column->entityMapKey));
            }

            return null;
        }

        $primitiveType = $column->type->getPrimitiveType();
        if (!$primitiveType->matches($value)) {
            throw new OrmQueryException(OrmError::VALUE_TYPE_ERROR,
                sprintf('Column "%s" value is expected to be of type "%s", got "%s"',
                    $column->entityMapKey,
                    $primitiveType->value,
                    gettype($value)));
        }

        if (is_string($value)) {
            $valueLength = strlen($value);
            if ($column->byteLen && $valueLength > $column->byteLen) {
                throw new \OverflowException(sprintf(
                    'Value of %d bytes exceeds column "%s" limit of %d bytes',
                    $valueLength,
                    $column->name,
                    $column->byteLen
                ));
            }
        }

        return $value;
    }
}
