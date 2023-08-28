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

    public readonly ColumnAttributes $attributes;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->attributes = new ColumnAttributes($name);
        $this->attributesCallback();
    }

    /**
     * @return void
     */
    protected function attributesCallback(): void
    {
    }

    /**
     * @return \Charcoal\Database\ORM\Schema\Columns\ColumnAttributes[]
     */
    public function __serialize(): array
    {
        return ["attributes" => $this->attributes];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->attributes = $data["attributes"];
        $this->attributesCallback();
    }

    /**
     * @return $this
     */
    public function isNullable(): static
    {
        $this->attributes->nullable = true;
        return $this;
    }

    /**
     * @param int|string|float|null $value
     * @return $this
     */
    protected function setDefaultValue(null|int|string|float $value): static
    {
        if (is_null($value) && !$this->attributes->nullable) {
            throw new \InvalidArgumentException(
                sprintf('Default value for col "%s" cannot be NULL; Column is not nullable', $this->attributes->name)
            );
        }

        $this->attributes->defaultValue = $value;
        return $this;
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    abstract public function getColumnSQL(DbDriver $driver): ?string;
}
