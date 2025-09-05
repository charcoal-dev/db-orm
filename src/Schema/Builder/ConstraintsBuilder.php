<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder;

use Charcoal\Base\Registry\Traits\InstancedObjectsRegistry;
use Charcoal\Base\Registry\Traits\RegistryKeysLowercaseTrimmed;
use Charcoal\Database\Orm\Schema\Builder\Constraints\AbstractConstraint;
use Charcoal\Database\Orm\Schema\Builder\Constraints\ForeignKeyConstraint;
use Charcoal\Database\Orm\Schema\Builder\Constraints\IndexKeyConstraint;
use Charcoal\Database\Orm\Schema\Builder\Constraints\UniqueKeyConstraint;

/**
 * Represents a collection of database constraints, including unique keys, foreign keys,
 * and indices. Provides methods to manage and retrieve these constraints.
 * @use \IteratorAggregate<string,AbstractConstraint>
 * @property array<string,AbstractConstraint> $instances
 */
class ConstraintsBuilder implements \IteratorAggregate
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
