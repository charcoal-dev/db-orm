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
use Charcoal\Database\ORM\Schema\Migrations;
use Charcoal\Database\Queries\DbExecutedQuery;

/**
 * Class UsersTable
 * @package Charcoal\Tests\ORM
 */
class UsersTable extends AbstractOrmTable
{
    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(4)->unSigned()->autoIncrement();
        $cols->enum("status")->options("active", "frozen", "disabled")->default("active");
        $cols->enum("role")->options("user", "mod")->default("active");
        $cols->binary("checksum")->fixed(20);
        $cols->string("username")->length(16)->unique();
        $cols->string("email")->length(32)->unique();
        $cols->string("first_name")->charset(Charset::UTF8MB4)->length(32)->isNullable();
        $cols->string("last_name")->charset(Charset::UTF8MB4)->length(32)->isNullable();
        $cols->string("country")->fixed(3)->isNullable();
        $cols->int("joined_on")->bytes(4)->unSigned();
        $cols->setPrimaryKey("id");
    }

    protected function migrations(Migrations $migrations): void
    {
        $migrations->add(0, function (Database $db, self $table): string {
            return implode("", Migrations::createTable($db, $table, true,
                "id", "status", "role", "checksum", "username", "email", "first_name", "last_name", "joined_on"
            ));
        });

        $migrations->add(1, function (Database $db, self $table): string {
            return Migrations::alterTableAddColumn($db, $table, "country", previous: "last_name");
        });
    }

    protected function onConstruct(): void
    {
    }

    public function newModelObject(): object|null
    {
        return new User();
    }

    /**
     * @param int $userId
     * @return \Charcoal\Tests\ORM\User
     * @throws \Charcoal\Database\ORM\Exception\OrmModelNotFoundException
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function findById(int $userId): User
    {
        /** @var \Charcoal\Tests\ORM\User */
        return $this->queryFind("WHERE `id`=?", [$userId])->getNext();
    }

    /**
     * @param \Charcoal\Tests\ORM\User $user
     * @return \Charcoal\Database\Queries\DbExecutedQuery
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function insert(User $user): DbExecutedQuery
    {
        return $this->queryInsert($user, false);
    }
}
