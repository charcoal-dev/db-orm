<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema;

use Charcoal\Base\Objects\Traits\NoDumpTrait;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Database\DatabaseClient;
use Charcoal\Database\Orm\Schema\Snapshot\TableSnapshot;

/**
 * Class TableMigrations
 * @package Charcoal\Database\Orm\Schema
 */
class TableMigrations
{
    private array $migrations = [];

    use NoDumpTrait;
    use NotSerializableTrait;
    use NotCloneableTrait;

    public function __construct(
        public readonly string        $table,
        public readonly TableSnapshot $snapshot,
    )
    {
    }

    /**
     * @param int $version
     * @param \Closure(DatabaseClient, string, TableSnapshot, int):array<string> $migrationProvider
     * @return $this
     */
    public function add(int $version, \Closure $migrationProvider): static
    {
        $this->migrations[strval($version)] = $migrationProvider;
        ksort($this->migrations);
        return $this;
    }

    /**
     * @internal
     */
    public function getQueries(DatabaseClient $db, int $versionFrom = 0, int $versionTo = 0): array
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
            $queriesSet = call_user_func_array($migration, [$db, $this->table, $this->snapshot, $version]);
            if (!is_array($queriesSet)) {
                throw new \UnexpectedValueException(sprintf(
                    'Unexpected value of type "%s" from "%s" migrations version %d',
                    gettype($queriesSet),
                    $this->table,
                    $version));
            }

            foreach ($queriesSet as $query) {
                if (!is_string($query)) {
                    throw new \UnexpectedValueException(sprintf(
                        'Unexpected value of type "%s" in one of the migrations for "%s" from version %d',
                        gettype($queriesSet),
                        $this->table,
                        $version));
                }

                $migrations[$version][] = $query;
            }
        }

        return $migrations;
    }
}
