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
 * Class FrameColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
final class FrameColumn extends BinaryColumn
{
    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Frame);
        $this->attributes->useValuePipe(ColumnPipes::FrameColumnPipe);
    }
}
