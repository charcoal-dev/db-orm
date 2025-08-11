<?php
/*
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Traits;

/**
 * Trait StringValueTrait
 * @package Charcoal\Database\Orm\Schema\Traits
 * @internal
 */
trait StringValueTrait
{
    /**
     * @param string $value
     * @return $this
     */
    final public function default(string $value): static
    {
        $this->setDefaultValue($value);
        return $this;
    }
}
