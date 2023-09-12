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

namespace Charcoal\Tests\ORM;

use Charcoal\Database\Database;
use Charcoal\Database\ORM\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;
use Charcoal\Database\Queries\DbExecutedQuery;
use Charcoal\OOP\Vectors\StringVector;

class BlobStoreTable extends AbstractOrmTable
{
    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->string("key")->length(40)->unique();
        $cols->binaryFrame("object")->length(1024);
        $cols->string("match_exp")->length(128)->nullable();
        $cols->int("timestamp")->bytes(4)->unSigned();
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }

    public function newChildObject(array $row): object|null
    {
        return null;
    }

    public function querySave(object|array $model, StringVector $updateCols, ?Database $db = null): DbExecutedQuery
    {
        return parent::querySave($model, $updateCols, $db);
    }
}