<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Pipes\ColumnPipes;

/**
 * Buffer column (backed by BLOB) - pipes to/from Buffer objects
 */
class BufferColumn extends BlobColumn
{
    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Buffer);
        $this->attributes->useValuePipe(ColumnPipes::BufferColumnPipe);
    }
}
