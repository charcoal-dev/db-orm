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
final class UniqueKeyConstraint extends AbstractConstraint
{
    private array $columns = [];
    private bool $isPrimary = false;

    /**
     * @param string ...$cols
     * @return $this
     */
    public function columns(string ...$cols): self
    {
        $this->columns = $cols;
        return $this;
    }

    /**
     * @return $this
     * @api
     */
    public function isPrimary(): self
    {
        $this->isPrimary = true;
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
            $this->getConstraintSQL($driver),
            $this->columns
        );
    }

    /**
     * @internal
     */
    public function getConstraintSQL(DbDriver $driver): ?string
    {
        $columns = implode(",", $this->columns);
        return match ($driver) {
            DbDriver::PGSQL,
            DbDriver::SQLITE => $this->isPrimary
                ? sprintf("CONSTRAINT %s PRIMARY KEY (%s)", $this->name, $columns)
                : sprintf("CONSTRAINT %s UNIQUE (%s)", $this->name, $columns),
            DbDriver::MYSQL => $this->isPrimary
                ? sprintf("CONSTRAINT %s PRIMARY KEY (%s)", $this->name, $columns)
                : sprintf("UNIQUE KEY %s (%s)", $this->name, $columns),
        };
    }
}

