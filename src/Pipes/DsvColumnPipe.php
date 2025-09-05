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

    public static function forEntity(string|int $value): DsvTokens
    {
        Runtime::assert(is_string($value),
            "DsvColumnPipe: value must be a string, got " . get_debug_type($value));

        return (new DsvTokens(changeCase: false, uniqueTokensOnly: true))->add(...explode(",", $value));
    }
}