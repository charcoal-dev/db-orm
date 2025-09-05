<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Orm\Enums\ColumnType;

/**
 * Class DsvColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 * @var class-string|null $enumClass
 */
class DsvColumn extends StringColumn
{
    protected string $enumClass;

    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Dsv);
    }

    /**
     * @param class-string<\StringBackedEnum> $enumClass
     * @return $this
     */
    public function enumClass(string $enumClass): static
    {
        $this->enumClass = $enumClass;
        return $this;
    }
}