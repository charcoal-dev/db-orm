<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm\Models;

use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\AbstractOrmTable;
use Charcoal\Database\Orm\Migrations;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;
use Charcoal\Vectors\Strings\StringVector;

/**
 * @api
 */
class UsersLogsTable extends AbstractOrmTable
{
    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
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
        $migrations->add(0, function (self $table): array {
            return [implode("", Migrations::createTable($table, true, new StringVector("id", "user", "log")))];
        });

        $migrations->add(6, function (self $table): array {
            return [Migrations::alterTableAddColumn($table, "added_on", "log")];
        });

        $migrations->add(7, function (self $table): array {
            return [Migrations::alterTableAddColumn($table, "ip_address", "added_on"),
                Migrations::alterTableAddColumn($table, "baggage", "ip_address")];
        });
    }

    public function newChildObject(array $row): object|null
    {
        return null;
    }
}

