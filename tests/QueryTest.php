<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm;

use Charcoal\Base\Vectors\StringVector;
use Charcoal\Buffers\Buffer;
use Charcoal\Database\Database;
use Charcoal\Database\DbCredentials;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Exception\QueryExecuteException;
use Charcoal\Database\Orm\Concerns\OrmError;
use Charcoal\Database\Orm\Exception\OrmQueryException;
use Charcoal\Database\Orm\OrmDbResolver;
use Charcoal\Database\Tests\Orm\Models\BlobModel;
use Charcoal\Database\Tests\Orm\Models\BlobStoreTable;

/**
 * Class QueryTest
 * @package Charcoal\Database\Tests\Orm
 */
class QueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Database\Exception\DbConnectionException
     */
    public function testSaveQuery(): void
    {
        OrmDbResolver::Bind(new Database(
            new DbCredentials(
                DbDriver::SQLITE,
                __DIR__ . "/tmp/sqlite-file-2"
            )
        ), BlobStoreTable::class);

        $blob = new BlobStoreTable("dataStore");

        try {
            $model = new BlobModel();
            $model->key = "some.key";
            $model->object = new Buffer("testvalue\0\0");
            $model->matchExp = null;
            $model->timestamp = 1234567;

            $blob->querySave($model, new StringVector("object", "matchExp", "timestamp"));
        } catch (\Exception $e) {
            $this->assertInstanceOf(OrmQueryException::class, $e);
            $this->assertEquals(OrmError::tryFrom($e->ormError->value)->name,
                OrmError::QUERY_EXECUTE->name);

            $this->assertInstanceOf(QueryExecuteException::class, $e->getPrevious());
            /** @var QueryExecuteException $queryExecEx */
            $queryExecEx = $e->getPrevious();

            $expectedQueryStr = 'INSERT INTO `dataStore` (`key`, `object`, `match_exp`, `timestamp`) VALUES (:key, ' .
                ':object, :match_exp, :timestamp) ON DUPLICATE KEY UPDATE `object`=:object, `match_exp`=:match_exp, ' .
                '`timestamp`=:timestamp';

            $this->assertEquals($expectedQueryStr, $queryExecEx->queryStr);
            $this->assertCount(4, $queryExecEx->boundData);
            $this->assertEquals("some.key", $queryExecEx->boundData["key"]);
            $this->assertEquals("testvalue\0\0", $queryExecEx->boundData["object"]);
            $this->assertArrayHasKey("match_exp", $queryExecEx->boundData);
            $this->assertNull($queryExecEx->boundData["match_exp"]);
            $this->assertEquals(1234567, $queryExecEx->boundData["timestamp"]);
            return;
        }

        $this->fail('Query had to be unsuccessful');
    }
}
