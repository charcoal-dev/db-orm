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

namespace Charcoal\Database\ORM\Schema;

use Charcoal\Database\ORM\AbstractOrmTable;
use Charcoal\Database\ORM\Exception\OrmModelMapException;
use Charcoal\Database\ORM\Exception\OrmModelNotFoundException;
use Charcoal\OOP\Vectors\ExceptionLog;

/**
 * Class ModelMapper
 * @package Charcoal\Database\ORM
 */
class ModelMapper
{
    /**
     * @param \Charcoal\Database\ORM\AbstractOrmTable $tableSchema
     */
    public function __construct(
        private readonly AbstractOrmTable $tableSchema
    )
    {
    }

    /**
     * Maps given assoc array using table's defined schema to model classes
     * NOTE: Any remaining values from input array, if any, are mapped into "unmapped" prop of model class, if such property exists
     * @param bool|array|null $row
     * @param ExceptionLog|null $errorLog
     * @return object|array
     * @throws OrmModelMapException
     * @throws OrmModelNotFoundException
     * @throws \Exception
     */
    public function mapSingle(bool|null|array $row, ?ExceptionLog $errorLog = null): object|array
    {
        if (!is_array($row)) {
            throw new OrmModelNotFoundException();
        }

        $object = $this->tableSchema->newChildObject($row);
        if (!$object) { // No new blank object given by table, return array as-is
            return $row;
        }

        /** @var \Charcoal\Database\ORM\Schema\Columns\AbstractColumn $column */
        foreach ($this->tableSchema->columns as $column) {
            unset($prop, $value);

            if (!property_exists($object, $column->attributes->modelProperty)) {
                continue;
            }

            $prop = $column->attributes->modelProperty;
            if (!isset($row[$column->attributes->name])) {
                if ($column->attributes->nullable) {
                    $object->$prop = null;
                }

                continue;
            }

            try {
                $value = $column->attributes->getResolvedModelProperty($row[$column->attributes->name]);
            } catch (\Exception $e) {
                if ($errorLog) {
                    $errorLog->append($e); // Append to ExceptionLog
                    continue; // Leave property uninitialized
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
                            $column->attributes->modelProperty
                        ),
                        previous: $t
                    );
                }
            } catch (OrmModelMapException $e) {
                if ($errorLog) {
                    $errorLog->append($e); // Append to ExceptionLog
                    continue; // Leave property uninitialized
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
