<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

use Charcoal\Base\Vectors\ExceptionVector;
use Charcoal\Database\Exception\DbQueryException;
use Charcoal\Database\Orm\Concerns\OrmError;
use Charcoal\Database\Orm\Exception\OrmQueryException;
use Charcoal\Database\Orm\Exception\OrmModelMapException;
use Charcoal\Database\Orm\Exception\OrmModelNotFoundException;
use Charcoal\Database\Orm\Schema\ModelMapper;
use Charcoal\Database\Queries\FetchQuery;

/**
 * Class OrmFetchQuery
 * @package Charcoal\Database\Orm
 */
class OrmFetchQuery extends ModelMapper
{
    public function __construct(
        private readonly FetchQuery $metaQuery,
        AbstractOrmTable            $tableSchema
    )
    {
        parent::__construct($tableSchema);
    }

    public function getCount(): int
    {
        return $this->metaQuery->query->rowsCount;
    }

    /**
     * @throws OrmModelMapException
     * @throws OrmModelNotFoundException
     * @throws OrmQueryException
     */
    public function getNext(?ExceptionVector $errorLog = null): object|array
    {
        try {
            return $this->mapSingle($this->metaQuery->getNext(), $errorLog);
        } catch (DbQueryException $e) {
            throw new OrmQueryException(OrmError::QUERY_FETCH, $e->getMessage(), previous: $e);
        }
    }

    /**
     * @throws OrmModelMapException
     * @throws OrmModelNotFoundException
     * @throws OrmQueryException
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
