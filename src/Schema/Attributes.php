<?php
/*
 * This file is a part of "charcoal-dev/db-orm" package.
 * https://github.com/charcoal-dev/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/db-orm/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Database\ORM\Schema;

/**
 * Class Attributes
 * @package Charcoal\Database\ORM\Schema
 */
class Attributes
{
    /** @var string */
    public string $mysqlEngine = "InnoDB";
    /** @var array */
    private array $arbitrary = [];

    /**
     * @param string $key
     * @param int|string $value
     * @return $this
     */
    public function set(string $key, int|string $value): static
    {
        $this->arbitrary[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return int|string|null
     */
    public function get(string $key): int|string|null
    {
        return $this->arbitrary[$key] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->arbitrary[$key]);
    }
}
