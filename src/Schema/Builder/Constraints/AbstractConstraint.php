<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Constraints;

use Charcoal\Database\Enums\DbDriver;

/**
 * Class AbstractConstraint
 * @package Charcoal\Database\Orm\Schema\Constraints
 */
abstract class AbstractConstraint
{
    public function __construct(public readonly string $name)
    {
        if (!$this->name || !preg_match('/^[a-zA-Z0-9_]+$/', $this->name)) {
            throw new \InvalidArgumentException(sprintf('Constraint name "%s" is invalid', $this->name));
        }
    }

    abstract public function getConstraintSQL(DbDriver $driver): ?string;
}
