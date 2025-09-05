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

/**
 * Store/Retrieve \BackedEnum values from the database.
 */
final readonly class BackedEnumColumnPipe implements ColumnValuePipeInterface
{
    public static function forDb(mixed $value, ColumnSnapshot $context): string
    {
        Runtime::assert($value instanceof \BackedEnum,
            "BackedEnumColumnPipe: value must be a BackedEnum, got " . get_debug_type($value));

        /** @var $value \BackedEnum */
        return (string)$value->value;
    }

    public static function forEntity(string|int|array|float|bool $value, ColumnSnapshot $context): \BackedEnum
    {
        Runtime::assert(is_string($value) || is_int($value),
            "BackedEnumColumnPipe: value must be a string|int, got " . get_debug_type($value));

        /** @var class-string<\BackedEnum> $enum */
        $enum = $context->pipeContext["enum"] ?? null;
        if (!enum_exists($enum)) {
            throw new \LogicException("Enum class does not exist: " . $context->name);
        }

        return $enum::from($value);
    }

    public static function validate(array $context): void
    {
        if (!isset($context["enum"]) || !enum_exists($context["enum"])) {
            throw new \LogicException("Enum class does not exist");
        }
    }
}