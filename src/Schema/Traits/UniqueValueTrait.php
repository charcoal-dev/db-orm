<?php
/*
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Traits;

/**
 * Trait UniqueValueTrait
 * @package Charcoal\Database\Orm\Schema\Traits
 * @internal
 */
trait UniqueValueTrait
{
    /**
     * @return $this
     */
    public function unique(): static
    {
        $this->attributes->unique = true;
        return $this;
    }
}
