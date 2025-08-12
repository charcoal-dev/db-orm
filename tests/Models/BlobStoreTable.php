<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm\Models;

use Charcoal\Base\Enums\Charset;
use Charcoal\Database\Orm\AbstractOrmTable;
use Charcoal\Database\Orm\Schema\Columns;
use Charcoal\Database\Orm\Schema\Constraints;
use Charcoal\Database\Orm\Schema\TableMigrations;

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
}