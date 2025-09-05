<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Constraints;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ConstraintType;
use Charcoal\Database\Orm\Schema\Snapshot\ConstraintSnapshot;

/**
 * Class UniqueKeyConstraint
 * @package Charcoal\Database\Orm\Schema\Constraints
 * @property array<string> $columns
 */
class UniqueKeyConstraint extends AbstractConstraint
{
    private array $columns = [];

    public function columns(string ...$cols): static
    {
        $this->columns = $cols;
        return $this;
    }

    /**
     * @internal
     */
    public function snapshot(DbDriver $driver): ConstraintSnapshot
    {
        return new ConstraintSnapshot(
            $this->name,
            ConstraintType::UniqueKey,
            $this->columns
        );
    }

    /**
     * @internal
     */
    public function getConstraintSQL(DbDriver $driver): ?string
    {
        $columns = implode(",", array_map(function ($col) {
            return sprintf('`%s`', $col);
        }, $this->columns));

        return match ($driver) {
            DbDriver::MYSQL => sprintf('UNIQUE KEY `%s` (%s)', $this->name, $columns),
            DbDriver::SQLITE => sprintf('CONSTRAINT `%s` UNIQUE (%s)', $this->name, $columns),
            default => null,
        };
    }
}

