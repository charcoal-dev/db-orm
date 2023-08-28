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

namespace Charcoal\Database\ORM\Schema;

use Charcoal\Database\Database;
use Charcoal\Database\ORM\AbstractDbTable;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class Migrations
 * @package Charcoal\Database\ORM\Schema
 */
class Migrations
{
    private array $migrations = [];

    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    /**
     * @param \Charcoal\Database\ORM\AbstractDbTable $table
     */
    public function __construct(public readonly AbstractDbTable $table)
    {
    }

    /**
     * @param int $version
     * @param \Closure $migrationProvider
     * @return $this
     */
    public function add(int $version, \Closure $migrationProvider): static
    {
        $this->migrations[strval($version)] = $migrationProvider;
        ksort($this->migrations);
        return $this;
    }

    /**
     * @param \Charcoal\Database\Database $db
     * @return array
     */
    public function getAll(Database $db): array
    {
        $migrations = [];
        foreach ($this->migrations as $version => $migration) {
            $queryStr = call_user_func_array($migration, [$db, $this->table, $version]);
            if (is_null($queryStr)) {
                continue;
            }

            if (!is_string($queryStr)) {
                throw new \UnexpectedValueException(sprintf(
                    'Expected value of type "string" from migrations version %d callback fn, got "%s"',
                    $version,
                    gettype($queryStr)
                ));
            }

            $migrations[] = $queryStr;
        }

        return $migrations;
    }
}
