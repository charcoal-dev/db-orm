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
use Charcoal\Database\Orm\Schema\Columns\AbstractColumn;

/**
 * Handles the mapping of raw database rows to entity objects or arrays, based on the schema
 * provided by the given AbstractOrmTable instance. The class resolves column attributes
 * and populates entity properties accordingly, adhering to the constraints and requirements
 * defined by the schema.
 */
final readonly class EntityMapper
{
    public function __construct(
        private AbstractOrmTable $tableSchema
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

        $object = $this->tableSchema->newChildObject($row);
        if (!$object) { // No new blank object given by table, return an array as-is
            return $row;
        }

        /** @var AbstractColumn $column */
        foreach ($this->tableSchema->columns as $column) {
            unset($prop, $value);

            if (!property_exists($object, $column->attributes->modelMapKey)) {
                continue;
            }

            $prop = $column->attributes->modelMapKey;
            if (!isset($row[$column->attributes->name])) {
                if ($column->attributes->nullable) {
                    $object->$prop = null;
                }

                continue;
            }

            try {
                $value = $column->attributes->resolveForModelProperty($row[$column->attributes->name]);
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
                            $column->attributes->modelMapKey
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

            unset($row[$column->attributes->name]);
        }

        if ($row) {
            if (property_exists($object, "unmapped")) {
                $object->unmapped = $row;
            }
        }

        return $object;
    }
}
