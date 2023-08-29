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
use Charcoal\Database\ORM\AbstractOrmTable;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class TableMigrations
 * @package Charcoal\Database\ORM\Schema
 */
class TableMigrations
{
    private array $migrations = [];

    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    /**
     * @param \Charcoal\Database\ORM\AbstractOrmTable $table
     */
    public function __construct(public readonly AbstractOrmTable $table)
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
     * @param int $versionFrom
     * @param int $versionTo
     * @return array
     */
    public function getQueries(Database $db, int $versionFrom = 0, int $versionTo = 0): array
    {
        $migrations = [];
        foreach ($this->migrations as $version => $migration) {
            $version = intval($version);
            if ($version < $versionFrom) {
                continue;
            }

            if ($version > $versionTo) {
                break;
            }

            $migrations[$version] = [];
            $queriesSet = call_user_func_array($migration, [$db, $this->table, $version]);
            if (!is_array($queriesSet)) {
                throw new \UnexpectedValueException(sprintf(
                    'Unexpected value of type "%s" from "%s" migrations version %d',
                    gettype($queriesSet),
                    $this->table->name,
                    $version
                ));
            }

            foreach ($queriesSet as $query) {
                if (!is_string($query)) {
                    throw new \UnexpectedValueException(sprintf(
                        'Unexpected value of type "%s" in one of the migrations for "%s" from version %d',
                        gettype($queriesSet),
                        $this->table->name,
                        $version
                    ));
                }

                $migrations[$version][] = $query;
            }
        }

        return $migrations;
    }
}
