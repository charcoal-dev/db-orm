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

use Charcoal\Database\Exception\DbQueryException;
use Charcoal\Database\ORM\Exception\OrmModelNotFoundException;
use Charcoal\Database\ORM\Exception\OrmQueryError;
use Charcoal\Database\ORM\Exception\OrmQueryException;
use Charcoal\Database\Queries\DbFetchQuery;

/**
 * Class OrmModelMapper
 * @package Charcoal\Database\ORM
 */
class OrmModelMapper
{
    /**
     * @param \Charcoal\Database\Queries\DbFetchQuery $query
     * @param \Charcoal\Database\ORM\AbstractDbTable $tableSchema
     */
    public function __construct(
        private readonly DbFetchQuery    $query,
        private readonly AbstractDbTable $tableSchema
    )
    {
    }

    /**
     * @return object|array
     * @throws \Charcoal\Database\ORM\Exception\OrmModelNotFoundException
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function getNext(): object|array
    {
        try {
            return $this->mapSingle($this->query->getNext());
        } catch (DbQueryException $e) {
            throw new OrmQueryException(OrmQueryError::QUERY_FETCH_EX, $e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array
     * @throws \Charcoal\Database\ORM\Exception\OrmModelNotFoundException
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function getAll(): array
    {
        try {
            $rows = $this->query->getAll();
        } catch (DbQueryException $e) {
            throw new OrmQueryException(OrmQueryError::QUERY_FETCH_EX, $e->getMessage(), previous: $e);
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->mapSingle($row);
        }

        return $result;
    }

    /**
     * Maps given assoc array using table's defined schema to model classes
     * NOTE: Any remaining values from input array, if any, are mapped into "unmapped" prop of model class, if such property exists
     * @param bool|array|null $row
     * @return object|array
     * @throws \Charcoal\Database\ORM\Exception\OrmModelNotFoundException
     */
    private function mapSingle(bool|null|array $row): object|array
    {
        if (!is_array($row)) {
            throw new OrmModelNotFoundException();
        }

        $object = $this->tableSchema->newModelObject();
        if (!$object) { // No new blank object given by table, return array as-is
            return $row;
        }

        /** @var \Charcoal\Database\ORM\Schema\Columns\AbstractColumn $column */
        foreach ($this->tableSchema->columns as $column) {
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

            $object->$prop = $column->attributes->getResolvedModelProperty($row[$column->attributes->name]);
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
