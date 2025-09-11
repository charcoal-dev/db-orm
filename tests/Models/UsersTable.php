<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm\Models;

use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\AbstractOrmTable;
use Charcoal\Database\Orm\Exceptions\OrmEntityMappingException;
use Charcoal\Database\Orm\Exceptions\OrmEntityNotFoundException;
use Charcoal\Database\Orm\Exceptions\OrmQueryException;
use Charcoal\Database\Orm\Migrations;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;
use Charcoal\Database\Queries\ExecutedQuery;
use Charcoal\Vectors\Strings\StringVector;

class UsersTable extends AbstractOrmTable
{
    public const string TABLE = "users";
    public string $modelClass = User::class;

    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->range(1, 10000)->unSigned()->autoIncrement();
        $cols->enum("status")->options("active", "frozen", "disabled")->default("active");
        $cols->enum("role", enumClass: UserRole::class)->options("user", "mod")->default("user");
        $cols->bool("is_deleted")->default(false);
        $cols->bool("test_bool_2");
        $cols->binaryFrame("checksum")->fixed(20);
        $cols->string("username")->length(16)->unique();
        $cols->string("email")->length(32)->unique();
        $cols->string("first_name")->charset(Charset::UTF8)->length(32)->nullable();
        $cols->string("last_name")->charset(Charset::UTF8)->length(32)->nullable();
        $cols->string("country")->fixed(3)->nullable();
        $cols->int("joined_on")->size(4)->unSigned()->enforceChecks();
        $cols->setPrimaryKey("id");
    }

    protected function migrations(TableMigrations $migrations): void
    {
        $migrations->add(0, function (self $table): array {
            return [implode("", Migrations::createTable($table, true,
                new StringVector("id", "status", "role", "checksum", "username", "email",
                    "first_name", "last_name", "joined_on")
            ))];
        });

        $migrations->add(7, function (self $table): array {
            return [Migrations::alterTableAddColumn($table, "country", "last_name")];
        });
    }

    public function newChildObject(array $row): object|null
    {
        return new $this->modelClass();
    }

    /**
     * @throws OrmQueryException
     * @throws OrmEntityMappingException
     * @throws OrmEntityNotFoundException
     * @api
     */
    public function findById(int $userId): User
    {
        /** @var User */
        return $this->queryFind("WHERE `id`=?", [$userId])->getNext();
    }

    /**
     * @throws OrmQueryException
     * @api
     */
    public function insert(User $user): ExecutedQuery
    {
        return $this->queryInsert($user, false);
    }
}
