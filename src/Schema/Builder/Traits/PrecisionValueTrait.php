<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Traits;

/** @internal */
trait PrecisionValueTrait
{
    protected int $digits = 0;
    protected int $scale = 0;

    public function precision(int $digits, int $scale): static
    {
        // Precision digits
        if ($digits < 1 || $digits > static::MAX_DIGITS) {
            throw new \OutOfRangeException(sprintf('Precision digits must be between 1 and %d',
                static::MAX_DIGITS));
        }

        // Scale
        $maxScale = max($digits, static::MAX_SCALE);
        if ($scale < 0 || $scale > $maxScale) {
            throw new \OutOfRangeException(sprintf('Scale digits must be between 1 and %d', $maxScale));
        }

        // Set
        $this->digits = $digits;
        $this->scale = $scale;
        return $this;
    }
}
