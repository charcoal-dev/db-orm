<?php
/*
 * This file is a part of "charcoal-dev/db-orm" package.
 * https://github.com/charcoal-dev/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/db-orm/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Database\ORM\Schema\Constraints;

use Charcoal\Database\DbDriver;

/**
 * Class ForeignKeyConstraint
 * @package Charcoal\Database\ORM\Schema\Constraints
 */
class ForeignKeyConstraint extends AbstractConstraint
{
    /** @var string */
    private string $table;
    /** @var string */
    private string $col;
    /** @var null|string */
    private ?string $db = null;

    /**
     * @param string $table
     * @param string $column
     * @return $this
     */
    public function table(string $table, string $column): static
    {
        $this->table = $table;
        $this->col = $column;
        return $this;
    }

    /**
     * @param string $db
     * @return $this
     */
    public function database(string $db): static
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getConstraintSQL(DbDriver $driver): ?string
    {
        $tableReference = $this->db ? sprintf('`%s`.`%s`', $this->db, $this->table) : sprintf('`%s`', $this->table);
        return match ($driver->value) {
            "mysql" => sprintf('FOREIGN KEY (`%s`) REFERENCES %s(`%s`)', $this->name, $tableReference, $this->col),
            "sqlite" => sprintf(
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

