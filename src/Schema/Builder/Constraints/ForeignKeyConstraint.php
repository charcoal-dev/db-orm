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
 * Class ForeignKeyConstraint
 * @package Charcoal\Database\Orm\Schema\Constraints
 */
class ForeignKeyConstraint extends AbstractConstraint
{
    private string $table;
    private string $col;
    private ?string $db = null;

    public function table(string $table, string $column): static
    {
        $this->table = $table;
        $this->col = $column;
        return $this;
    }

    public function database(string $db): static
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @internal
     */
    public function snapshot(DbDriver $driver): ConstraintSnapshot
    {
        return new ConstraintSnapshot(
            $this->name,
            ConstraintType::ForeignKey,
            $this->getConstraintSQL($driver),
            [$this->col],
            $this->table,
            $this->db
        );
    }

    /**
     * @internal
     */
    public function getConstraintSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::SQLITE,
            DbDriver::PGSQL,
            DbDriver::MYSQL => sprintf(
                "CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s)",
                sprintf("constraint_%s_foreign", $this->name),
                $this->name,
                $this->db ? sprintf("%s.%s", $this->db, $this->table) : $this->table,
                $this->col
            )
        };
    }
}

