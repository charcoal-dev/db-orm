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
 * Class UniqueKeyConstraint
 * @package Charcoal\Database\ORM\Schema\Constraints
 */
class UniqueKeyConstraint extends AbstractConstraint
{
    /** @var array */
    private array $columns = [];

    /**
     * @param string ...$cols
     * @return $this
     */
    public function columns(string ...$cols): static
    {
        $this->columns = $cols;
        return $this;
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getConstraintSQL(DbDriver $driver): ?string
    {
        $columns = implode(",", array_map(function ($col) {
            return sprintf('`%s`', $col);
        }, $this->columns));

        return match ($driver->value) {
            "mysql" => sprintf('UNIQUE KEY `%s` (%s)', $this->name, $columns),
            "sqlite" => sprintf('CONSTRAINT `%s` UNIQUE (%s)', $this->name, $columns),
            default => null,
        };
    }
}

