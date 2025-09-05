<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

use Charcoal\Contracts\Vectors\ExceptionsVectorInterface;
use Charcoal\Database\Exceptions\DbQueryException;
use Charcoal\Database\Orm\Enums\OrmError;
use Charcoal\Database\Orm\Exceptions\OrmQueryException;
use Charcoal\Database\Orm\Exceptions\OrmEntityMappingException;
use Charcoal\Database\Orm\Exceptions\OrmEntityNotFoundException;
use Charcoal\Database\Orm\Schema\EntityMapper;
use Charcoal\Database\Queries\FetchQuery;

/**
 * Class OrmFetchQuery
 * @package Charcoal\Database\Orm
 */
final readonly class OrmFetchQuery extends EntityMapper
{
    public function __construct(
        private FetchQuery $metaQuery,
        AbstractOrmTable   $tableSchema
    )
    {
        parent::__construct($tableSchema);
    }

    /**
     * @api
     */
    public function getCount(): int
    {
        return $this->metaQuery->query->rowsCount;
    }

    /**
     * @throws OrmEntityMappingException
     * @throws OrmEntityNotFoundException
     * @throws OrmQueryException
     */
    public function getNext(?ExceptionsVectorInterface $errorLog = null): object|array
    {
        try {
            return $this->mapSingle($this->metaQuery->getNext(), $errorLog);
        } catch (DbQueryException $e) {
            throw new OrmQueryException(OrmError::QUERY_FETCH, $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws OrmEntityMappingException
     * @throws OrmEntityNotFoundException
     * @throws OrmQueryException
     * @api
     */
    public function getAll(): array
    {
        try {
            $rows = $this->metaQuery->getAll();
        } catch (DbQueryException $e) {
            throw new OrmQueryException(OrmError::QUERY_FETCH, $e->getMessage(), previous: $e);
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->mapSingle($row);
        }

        return $result;
    }
}
