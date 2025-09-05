<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Base\Support\Runtime;
use Charcoal\Database\Orm\Contracts\ColumnValuePipeInterface;
use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;
use Charcoal\Vectors\Support\DsvTokens;

/**
 * Store/Retrieve DsvTokens from the database.
 */
final readonly class DsvColumnPipe implements ColumnValuePipeInterface
{
    public static function forDb(mixed $value, ColumnSnapshot $context): string
    {
        Runtime::assert($value instanceof DsvTokens,
            "DsvColumnPipe: value must be a DsvTokens, got " . get_debug_type($value));

        /** @var $value DsvTokens */
        return $value->join(",");
    }

    public static function forEntity(string|int|array $value, ColumnSnapshot $context): DsvTokens
    {
        Runtime::assert(is_string($value) || is_array($value),
            "DsvColumnPipe: value must be a string|Array, got " . get_debug_type($value));

        if (!is_array($value)) {
            $value = explode(",", $value);
        }

        return (new DsvTokens(changeCase: false, uniqueTokensOnly: true))->add(...$value);
    }

    public static function validate(array $context): void
    {
        if (!isset($context["delimiter"])
            || !is_string($context["delimiter"])
            || strlen($context["delimiter"]) !== 1) {
            throw new \LogicException("DsvColumnPipe: delimiter was not stored");
        }

        if (isset($context["enum"])) {
            if (!enum_exists($context["enum"])) {
                throw new \LogicException("Enum class does not exist: " . $context["enum"]);
            }
        }
    }
}