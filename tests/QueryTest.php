<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm;

use Charcoal\Buffers\Buffer;
use Charcoal\Database\DatabaseClient;
use Charcoal\Database\Config\DbCredentials;
use Charcoal\Database\Enums\DbConnectionStrategy;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Exceptions\QueryExecuteException;
use Charcoal\Database\Orm\Enums\OrmError;
use Charcoal\Database\Orm\Exceptions\OrmQueryException;
use Charcoal\Database\Orm\OrmDbResolver;
use Charcoal\Database\Tests\Orm\Models\BlobModel;
use Charcoal\Database\Tests\Orm\Models\BlobStoreTable;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Class QueryTest
 * @package Charcoal\Database\Tests\Orm
 */
class QueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Database\Exceptions\DbConnectionException
     */
    public function testSaveQuery(): void
    {
        OrmDbResolver::Bind(new DatabaseClient(
            new DbCredentials(
                DbDriver::SQLITE,
                __DIR__ . "/tmp/sqlite-file-2",
                strategy: DbConnectionStrategy::Normal
            )
        ), BlobStoreTable::class);

        $blob = new BlobStoreTable("data_store", DbDriver::SQLITE);

        try {
            $model = new BlobModel();
            $model->objectId = "some.key";
            $model->bytes = new Buffer("testvalue\0\0");
            $model->matchExp = null;
            $model->updatedOn = 1234567;

            $blob->querySave($model, new StringVector("bytes", "matchExp", "updatedOn"));
        } catch (\Exception $e) {
            $this->assertInstanceOf(OrmQueryException::class, $e);
            $this->assertEquals(OrmError::tryFrom($e->ormError->value)->name,
                OrmError::QUERY_EXECUTE->name);

            $this->assertInstanceOf(QueryExecuteException::class, $e->getPrevious());
            /** @var QueryExecuteException $queryExecEx */
            $queryExecEx = $e->getPrevious();

            $expectedQueryStr = 'INSERT INTO data_store (object_id, bytes, match_exp, updated_on) VALUES (:object_id, ' .
                ':bytes, :match_exp, :updated_on) ON DUPLICATE KEY UPDATE bytes=:bytes, match_exp=:match_exp, ' .
                'updated_on=:updated_on';

            $this->assertEquals($expectedQueryStr, $queryExecEx->queryStr);
            $this->assertCount(4, $queryExecEx->boundData);
            $this->assertEquals("some.key", $queryExecEx->boundData["object_id"]);
            $this->assertEquals("testvalue\0\0", $queryExecEx->boundData["bytes"]);
            $this->assertArrayHasKey("match_exp", $queryExecEx->boundData);
            $this->assertNull($queryExecEx->boundData["match_exp"]);
            $this->assertEquals(1234567, $queryExecEx->boundData["updated_on"]);
            return;
        }

        $this->fail('Query had to be unsuccessful');
    }
}
