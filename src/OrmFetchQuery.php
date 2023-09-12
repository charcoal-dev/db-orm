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
use Charcoal\Database\ORM\Exception\OrmQueryError;
use Charcoal\Database\ORM\Exception\OrmQueryException;
use Charcoal\Database\ORM\Schema\ModelMapper;
use Charcoal\Database\Queries\DbFetchQuery;

/**
 * Class OrmFetchQuery
 * @package Charcoal\Database\ORM
 */
class OrmFetchQuery extends ModelMapper
{
    /**
     * @param \Charcoal\Database\Queries\DbFetchQuery $query
     * @param \Charcoal\Database\ORM\AbstractOrmTable $tableSchema
     */
    public function __construct(
        private readonly DbFetchQuery $query,
        AbstractOrmTable             $tableSchema
    )
    {
        parent::__construct($tableSchema);
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->query->query->rowsCount;
    }

    /**
     * @return object|array
     * @throws \Charcoal\Database\ORM\Exception\OrmException
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
     * @throws \Charcoal\Database\ORM\Exception\OrmException
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
}
