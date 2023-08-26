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

namespace Charcoal\Database\ORM\Schema\Columns;

use Charcoal\Database\DbDriver;

/**
 * Class AbstractColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
abstract class AbstractColumn
{
    public const PRIMITIVE_TYPE = null;

    /** @var int|float|string|null */
    protected int|float|string|null $defaultValue = null;
    /** @var array */
    protected array $attributes = [];

    /**
     * @param string $name
     * @param bool $nullable
     */
    public function __construct(
        public readonly string $name,
        public bool            $nullable = false,
    )
    {
    }

    /**
     * @return $this
     */
    public function isNullable(): static
    {
        $this->nullable = true;
        return $this;
    }

    /**
     * @param int|string|float|null $value
     * @return $this
     */
    protected function setDefaultValue(null|int|string|float $value): self
    {
        if (is_null($value) && !$this->nullable) {
            throw new \InvalidArgumentException(
                sprintf('Default value for col "%s" cannot be NULL; Column is not nullable', $this->name)
            );
        }

        $this->defaultValue = $value;
        return $this;
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    abstract public function getColumnSQL(DbDriver $driver): ?string;
}
