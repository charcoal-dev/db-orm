<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Pipes;

use Charcoal\Base\Enums\EnumHelper;
use Charcoal\Base\Support\Runtime;
use Charcoal\Contracts\Errors\ExceptionAction;
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

        if (!isset($context->pipeContext["delimiter"]) || strlen($context->pipeContext["delimiter"]) !== 1) {
            throw new \LogicException("DsvColumnPipe: delimiter was not stored");
        }

        if ($context->pipeContext["delimiter"] !== $value->delimiter) {
            throw new \LogicException("DsvColumnPipe: delimiters do not match");
        }

        if (isset($context->pipeContext["enum"])) {
            EnumHelper::validatedEnumCasesFromVector(
                $context->pipeContext["enum"],
                $value,
                ExceptionAction::Ignore
            );
        }

        /** @var $value DsvTokens */
        return $value->join($value->delimiter);
    }

    public static function forEntity(string|int|array|float|bool $value, ColumnSnapshot $context): DsvTokens
    {
        Runtime::assert(is_string($value) || is_array($value),
            "DsvColumnPipe: value must be a string|Array, got " . get_debug_type($value));

        if (!is_array($value)) {
            if (!isset($context->pipeContext["delimiter"]) || strlen($context->pipeContext["delimiter"]) !== 1) {
                throw new \LogicException("DsvColumnPipe: delimiter was not stored");
            }

            $value = explode($context->pipeContext["delimiter"], $value);
        }

        return (new DsvTokens(
            delimiter: $context->pipeContext["delimiter"],
            changeCase: false,
            uniqueTokensOnly: true
        ))->add(...$value);
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