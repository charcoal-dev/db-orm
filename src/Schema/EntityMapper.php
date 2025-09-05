<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema;

use Charcoal\Contracts\Vectors\ExceptionsVectorInterface;
use Charcoal\Database\Orm\AbstractOrmTable;
use Charcoal\Database\Orm\Exceptions\OrmEntityMappingException;
use Charcoal\Database\Orm\Exceptions\OrmEntityNotFoundException;
use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;

/**
 * Handles the mapping of raw database rows to entity objects or arrays, based on the schema
 * provided by the given AbstractOrmTable instance. The class resolves column attributes
 * and populates entity properties accordingly, adhering to the constraints and requirements
 * defined by the schema.
 */
readonly class EntityMapper
{
    public function __construct(
        private AbstractOrmTable $table
    )
    {
    }

    /**
     * @throws OrmEntityMappingException
     * @throws OrmEntityNotFoundException
     * @throws \Exception
     */
    public function mapSingle(bool|null|array $row, ?ExceptionsVectorInterface $errorLog = null): object|array
    {
        if (!is_array($row)) {
            throw new OrmEntityNotFoundException();
        }

        $object = $this->table->newChildObject($row);
        if (!$object) { // No new blank object given by table, return an array as-is
            return $row;
        }

        foreach ($this->table->snapshot->columns as $column) {
            unset($prop, $value);

            if (!property_exists($object, $column->entityMapKey)) {
                continue;
            }

            $prop = $column->entityMapKey;
            if (!isset($row[$column->name])) {
                if ($column->nullable) {
                    $object->$prop = null;
                }

                continue;
            }

            try {
                $value = $this->getPipedValue($row[$column->name], $column);
            } catch (\Exception $e) {
                if ($errorLog) {
                    $errorLog->append($e);
                    continue;
                }

                throw $e;
            }

            try {
                try {
                    $object->$prop = $value;
                } catch (\Throwable $t) {
                    throw new OrmEntityMappingException(
                        sprintf(
                            'Cannot map value of type "%s" to column "%s"',
                            gettype($value),
                            $column->entityMapKey
                        ),
                        previous: $t
                    );
                }
            } catch (OrmEntityMappingException $e) {
                if ($errorLog) {
                    $errorLog->append($e);
                    continue;
                }

                throw $e;
            }

            unset($row[$column->name]);
        }

        if ($row) {
            if (property_exists($object, "unmapped")) {
                $object->unmapped = $row;
            }
        }

        return $object;
    }

    /**
     * @param mixed $value
     * @param ColumnSnapshot $snapshot
     * @return mixed
     */
    public function getPipedValue(mixed $value, ColumnSnapshot $snapshot): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (!$snapshot->valuePipe) {
            return $value;
        }

        return $snapshot->valuePipe->forEntity($value, $snapshot);
    }
}
