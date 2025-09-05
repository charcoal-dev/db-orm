<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder;

use Charcoal\Database\Orm\Enums\MySqlEngine;
use Charcoal\Database\Orm\Schema\Snapshot\TableAttributes;

/**
 * A builder class for managing table attributes, allowing the user to
 * define and retrieve arbitrary key-value pairs and set the MySQL storage engine.
 */
class TableAttributesBuilder
{
    public ?MySqlEngine $mysqlEngine;
    private array $arbitrary = [];

    public function set(string $key, int|string $value): static
    {
        $this->arbitrary[$key] = $value;
        return $this;
    }

    public function get(string $key): int|string|null
    {
        return $this->arbitrary[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->arbitrary[$key]);
    }

    public function snapshot(): TableAttributes
    {
        return new TableAttributes($this->mysqlEngine);
    }
}
