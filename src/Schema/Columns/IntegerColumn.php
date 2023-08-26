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
use Charcoal\Database\ORM\Schema\Traits\NumericValueTrait;
use Charcoal\Database\ORM\Schema\Traits\UniqueValueTrait;

/**
 * Class IntegerColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class IntegerColumn extends AbstractColumn
{
    /** @var string */
    public const PRIMITIVE_TYPE = "integer";
    /** @var int */
    private int $size = 4; // Default 4 byte integer
    /** @var bool */
    private bool $autoIncrement = false;

    use NumericValueTrait;
    use UniqueValueTrait;

    /**
     * IntegerColumn constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->attributes->unSigned = true;
    }

    /**
     * @param int $byte
     * @return $this
     */
    public function size(int $byte): static
    {
        if (!in_array($byte, [1, 2, 3, 4, 8])) {
            throw new \OutOfBoundsException('Invalid integer size');
        }

        $this->size = $byte;
        return $this;
    }

    /**
     * @param int $byte
     * @return $this
     */
    public function bytes(int $byte): static
    {
        return $this->size($byte);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function default(int $value): static
    {
        if ($value < 0 && $this->attributes["unsigned"] === 1) {
            throw new \InvalidArgumentException('Cannot set signed integer as default value');
        }

        $this->setDefaultValue($value);
        return $this;
    }

    /**
     * @return $this
     */
    public function autoIncrement(): static
    {
        $this->autoIncrement = true;
        return $this;
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver->value) {
            "mysql" => match ($this->size) {
                1 => "tinyint",
                2 => "smallint",
                3 => "mediumint",
                8 => "bigint",
                default => "int",
            },
            default => "integer",
        };
    }
}
