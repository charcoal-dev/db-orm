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
 * Class EnumColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class EnumColumn extends AbstractColumn
{
    /** @var string */
    public const PRIMITIVE_TYPE = "string";
    /** @var array */
    private array $options = [];

    /**
     * @param string ...$opts
     * @return EnumColumn
     */
    public function options(string ...$opts): static
    {
        $this->options = $opts;
        return $this;
    }

    /**
     * @param string $opt
     * @return EnumColumn
     */
    public function default(string $opt): static
    {
        if (!in_array($opt, $this->options)) {
            throw new \OutOfBoundsException(
                sprintf('Default value for "%s" must be from defined options', $this->attributes->name)
            );
        }

        $this->setDefaultValue($opt);
        return $this;
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        $options = implode(",", array_map(function (string $opt) {
            return sprintf("'%s'", $opt);
        }, $this->options));

        return match ($driver->value) {
            "mysql" => sprintf('enum(%s)', $options),
            "sqlite" => sprintf('TEXT CHECK(%s in (%s))', $this->attributes->name, $options),
            default => null,
        };
    }
}

