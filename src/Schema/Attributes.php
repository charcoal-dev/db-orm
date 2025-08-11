<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema;

/**
 * Class Attributes
 * @package Charcoal\Database\Orm\Schema
 */
class Attributes
{
    public string $mysqlEngine = "InnoDB";
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
}
