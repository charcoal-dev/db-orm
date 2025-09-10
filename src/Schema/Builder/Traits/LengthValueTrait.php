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

    /**
     * Arbitrary length values within the specified maximum boundary.
     */
    public function length(int $length): static
    {
        if ($length < static::LENGTH_MIN || $length > static::LENGTH_MAX) {
            throw new \OutOfRangeException(
                sprintf('Maximum length for col "%s" cannot exceed %d',
                    $this->attributes->name,
                    static::LENGTH_MAX)
            );
        }

        $this->length = $length;
        return $this;
    }

    /**
     * Marks the column as fixed-length.
     * CHECK constraint will require the column to be exactly the specified length.
     * Value will not be automatically NULL padded.
     */
    public function fixed(int $length): static
    {
        $this->length($length);
        $this->fixed = true;
        return $this;
    }
}
