<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema;

use Charcoal\Base\Concerns\InstancedObjectsRegistry;
use Charcoal\Base\Concerns\RegistryKeysLowercaseTrimmed;
use Charcoal\Database\Orm\Schema\Constraints\AbstractConstraint;
use Charcoal\Database\Orm\Schema\Constraints\ForeignKeyConstraint;
use Charcoal\Database\Orm\Schema\Constraints\IndexKeyConstraint;
use Charcoal\Database\Orm\Schema\Constraints\UniqueKeyConstraint;

/**
 * Class Constraints
 * @package Charcoal\Database\Orm\Schema
 * @property array<string,AbstractConstraint> $instances
 */
class Constraints implements \IteratorAggregate
{
    use InstancedObjectsRegistry;
    use RegistryKeysLowercaseTrimmed;

    public function uniqueKey(string $key): UniqueKeyConstraint
    {
        $constraint = new UniqueKeyConstraint($key);
        $this->instances[$key] = $constraint;
        return $constraint;
    }

    public function foreignKey(string $key): ForeignKeyConstraint
    {
        $constraint = new ForeignKeyConstraint($key);
        $this->instances[$key] = $constraint;
        return $constraint;
    }

    public function addIndex(string $column, string $prefix = "idx_"): void
    {
        $key = $prefix . $column;
        $this->instances[$key] = (new IndexKeyConstraint($key))->columns($column);
    }

    public function addIndexComposite(string $key): IndexKeyConstraint
    {
        $constraint = new IndexKeyConstraint($key);
        $this->instances[$key] = $constraint;
        return $constraint;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->instances);
    }
}
