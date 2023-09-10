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

require_once "TestModels.php";

class QueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Database\Exception\DbConnectionException
     */
    public function testSaveQuery(): void
    {
        $db = new \Charcoal\Database\Database(new \Charcoal\Database\DbCredentials(\Charcoal\Database\DbDriver::SQLITE, "tmp/sqlite-file-1"));
        $blob = new \Charcoal\Tests\ORM\BlobStoreTable("dataStore");

        try {
            $model = new \Charcoal\Tests\ORM\BlobModel();
            $model->key = "some.key";
            $model->object = new \Charcoal\Buffers\Buffer("testvalue\0\0");
            $model->matchExp = null;
            $model->timestamp = 1234567;

            $blob->querySave($model, new \Charcoal\OOP\Vectors\StringVector("object", "matchExp", "timestamp"), $db);
        } catch (Exception $e) {
            $this->assertInstanceOf(\Charcoal\Database\ORM\Exception\OrmQueryException::class, $e);
            $this->assertInstanceOf(\Charcoal\Database\Exception\QueryExecuteException::class, $e->getPrevious());
            /** @var \Charcoal\Database\Exception\QueryExecuteException $queryExecEx */
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
