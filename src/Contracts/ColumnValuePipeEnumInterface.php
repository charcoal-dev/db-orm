<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Contracts;

use Charcoal\Database\Orm\Schema\Snapshot\ColumnSnapshot;

/**
 * Interface for resolving column values for different contexts.
 */
interface ColumnValuePipeEnumInterface
{
    public function forDb(mixed $value, ColumnSnapshot $context): string|int;

    public function forEntity(string|int|array|float|bool $value, ColumnSnapshot $context): mixed;

    public function validate(array $context): void;
}