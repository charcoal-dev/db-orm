<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema;

use Charcoal\Base\Vectors\ExceptionVector;
use Charcoal\Database\Orm\AbstractOrmTable;
use Charcoal\Database\Orm\Exceptions\OrmModelMapException;
use Charcoal\Database\Orm\Exceptions\OrmModelNotFoundException;
use Charcoal\Database\Orm\Schema\Columns\AbstractColumn;

/**
 * Class ModelMapper
 * @package Charcoal\Database\Orm
 */
class ModelMapper
{
    public function __construct(
        private readonly AbstractOrmTable $tableSchema
    )
    {
    }

    /**
     * @throws OrmModelMapException
     * @throws OrmModelNotFoundException
     * @throws \Exception
     */
    public function mapSingle(bool|null|array $row, ?ExceptionVector $errorLog = null): object|array
    {
        if (!is_array($row)) {
            throw new OrmModelNotFoundException();
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
                    throw new OrmModelMapException(
                        sprintf(
                            'Cannot map value of type "%s" to column "%s"',
                            gettype($value),
                            $column->attributes->modelMapKey
                        ),
                        previous: $t
                    );
                }
            } catch (OrmModelMapException $e) {
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
