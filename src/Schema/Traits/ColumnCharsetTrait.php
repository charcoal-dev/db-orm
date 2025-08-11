<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Traits;

use Charcoal\Base\Enums\Charset;

/**
 * Trait ColumnCharsetTrait
 * @package Charcoal\Database\Orm\Schema\Traits
 * @internal
 */
trait ColumnCharsetTrait
{
    final public function charset(Charset $charset): static
    {
        $this->attributes->charset = $charset;
        return $this;
    }
}

