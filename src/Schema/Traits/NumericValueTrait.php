<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Traits;

/**
 * Trait NumericValueTrait
 * @package Charcoal\Database\Orm\Schema\Traits
 * @internal
 */
trait NumericValueTrait
{
    final public function signed(): static
    {
        $this->attributes->unSigned = false;
        return $this;
    }

    final public function unSigned(): static
    {
        $this->attributes->unSigned = true;
        return $this;
    }
}
