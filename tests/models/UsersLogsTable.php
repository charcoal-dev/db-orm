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
use Charcoal\Database\ORM\Migrations;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;
use Charcoal\OOP\Vectors\StringVector;

class UsersLogsTable extends AbstractOrmTable
{
    public const TABLE = "users_logs";

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->int("user")->bytes(4)->unSigned();
        $cols->string("log")->length(512);
        $cols->int("added_on")->bytes(4)->unSigned();
        $cols->string("ip_address")->length(45);
        $cols->string("baggage")->length(100)->nullable();
        $cols->setPrimaryKey("id");

        $constraints->foreignKey("user")->table(UsersTable::TABLE, "id");
    }

    protected function migrations(TableMigrations $migrations): void
    {
        $migrations->add(0, function (Database $db, self $table): array {
            return [implode("", Migrations::createTable($db, $table, true, new StringVector("id", "user", "log")))];
        });

        $migrations->add(6, function (Database $db, self $table): array {
            return [Migrations::alterTableAddColumn($db, $table, "added_on", "log")];
        });

        $migrations->add(7, function (Database $db, self $table): array {
            return [Migrations::alterTableAddColumn($db, $table, "ip_address", "added_on"),
                Migrations::alterTableAddColumn($db, $table, "baggage", "ip_address")];
        });
    }

    public function newChildObject(array $row): object|null
    {
        return null;
    }
}

