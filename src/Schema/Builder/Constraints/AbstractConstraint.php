<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Constraints;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Schema\Snapshot\ConstraintSnapshot;

/**
 * Class AbstractConstraint
 * @package Charcoal\Database\Orm\Schema\Constraints
 */
abstract class AbstractConstraint
{
    public function __construct(public readonly string $name)
    {
        if (!$this->name || !preg_match('/^[a-z0-9_]+$/', $this->name)) {
            throw new \InvalidArgumentException(sprintf('Constraint name "%s" is invalid', $this->name));
        }
    }

    /** @internal */
    abstract public function snapshot(DbDriver $driver): ConstraintSnapshot;

    /** @internal */
    abstract public function getConstraintSQL(DbDriver $driver): ?string;
}
