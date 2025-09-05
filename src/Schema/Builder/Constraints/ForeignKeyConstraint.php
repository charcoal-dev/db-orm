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
        $tableReference = $this->db ? sprintf('`%s`.`%s`', $this->db, $this->table) : sprintf('`%s`', $this->table);
        return match ($driver) {
            DbDriver::MYSQL => sprintf('FOREIGN KEY (`%s`) REFERENCES %s(`%s`)', $this->name, $tableReference, $this->col),
            DbDriver::SQLITE => sprintf(
                'CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES %s(`%s`)',
                sprintf('cnstrnt_%s_frgn', $this->name),
                $this->name,
                $tableReference,
                $this->col
            ),
            default => null,
        };
    }
}

