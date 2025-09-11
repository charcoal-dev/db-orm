<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm\Models;

use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\AbstractOrmTable;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

class BlobStoreTable extends AbstractOrmTable
{
    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->string("object_id")->length(40)->unique();
        $cols->binaryFrame("bytes")->length(1024);
        $cols->string("match_exp")->length(128)->nullable();
        $cols->int("updated_on")->size(4)->unSigned();
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }

    public function newChildObject(array $row): object|null
    {
        return null;
    }
}