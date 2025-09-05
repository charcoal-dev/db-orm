<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Traits;

/** @internal */
trait LengthValueTrait
{
    protected int $length = 255;
    protected bool $fixed = false;

    public function length(int $length): static
    {
        if ($length < static::LENGTH_MIN || $length > static::LENGTH_MAX) {
            throw new \OutOfRangeException(
                sprintf('Maximum length for col "%s" cannot exceed %d', $this->attributes->name, static::LENGTH_MAX)
            );
        }

        $this->length = $length;
        return $this;
    }

    public function fixed(int $length): static
    {
        $this->length($length);
        $this->fixed = true;
        return $this;
    }
}
