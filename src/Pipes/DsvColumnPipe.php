<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Base\Support\Runtime;
use Charcoal\Vectors\Support\DsvTokens;

/**
 * Store/Retrieve DsvTokens from the database.
 */
final readonly class DsvColumnPipe
{
    public static function forDb(mixed $value): string
    {
        Runtime::assert($value instanceof DsvTokens,
            "DsvColumnPipe: value must be a DsvTokens, got " . get_debug_type($value));

        /** @var $value DsvTokens */
        return $value->join(",");
    }

    public static function forEntity(string|int|array $value): DsvTokens
    {
        Runtime::assert(is_string($value) || is_array($value),
            "DsvColumnPipe: value must be a string|Array, got " . get_debug_type($value));

        if (!is_array($value)) {
            $value = explode(",", $value);
        }

        return (new DsvTokens(changeCase: false, uniqueTokensOnly: true))->add(...$value);
    }
}