<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Orm\Pipes\ColumnPipes;

/**
 * Class BufferColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class BufferColumn extends BlobColumn
{
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->attributes->useValuePipe(ColumnPipes::BufferColumnPipe);
    }
}
