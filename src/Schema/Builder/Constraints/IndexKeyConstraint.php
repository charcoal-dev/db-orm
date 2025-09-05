<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Constraints;

use Charcoal\Database\Enums\DbDriver;

/**
 * Class IndexKeyConstraint
 * @package Charcoal\Database\Orm\Schema\Constraints
 * @property array<string> $columns
 */
class IndexKeyConstraint extends AbstractConstraint
{
    private array $columns = [];

    public function columns(string ...$cols): static
    {
        $this->columns = $cols;
        return $this;
    }

    public function getConstraintSQL(DbDriver $driver): ?string
    {
        $columns = implode(",", array_map(function ($col) {
            return sprintf('`%s`', $col);
        }, $this->columns));

        return match ($driver) {
            DbDriver::MYSQL => sprintf('INDEX `%s` (%s)', $this->name, $columns),
            default => null,
        };
    }
}