<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm\Models;

use Charcoal\Base\Enums\Charset;
use Charcoal\Base\Vectors\StringVector;
use Charcoal\Database\DatabaseClient;
use Charcoal\Database\Orm\AbstractOrmTable;
use Charcoal\Database\Orm\Migrations;
use Charcoal\Database\Orm\Schema\Columns;
use Charcoal\Database\Orm\Schema\Constraints;
use Charcoal\Database\Orm\Schema\TableMigrations;

class UsersLogsTable extends AbstractOrmTable
{
    public const string TABLE = "users_logs";

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
        $migrations->add(0, function (DatabaseClient $db, self $table): array {
            return [implode("", Migrations::createTable($db, $table, true, new StringVector("id", "user", "log")))];
        });

        $migrations->add(6, function (DatabaseClient $db, self $table): array {
            return [Migrations::alterTableAddColumn($db, $table, "added_on", "log")];
        });

        $migrations->add(7, function (DatabaseClient $db, self $table): array {
            return [Migrations::alterTableAddColumn($db, $table, "ip_address", "added_on"),
                Migrations::alterTableAddColumn($db, $table, "baggage", "ip_address")];
        });
    }

    public function newChildObject(array $row): object|null
    {
        return null;
    }
}

