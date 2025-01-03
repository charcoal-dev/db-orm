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
use Charcoal\Database\ORM\Schema\Traits\UniqueValueTrait;

/**
 * Class DateColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class DateColumn extends AbstractColumn
{
    public const PRIMITIVE_TYPE = "string";

    use UniqueValueTrait;

    /**
     * @param \DateTime|int|string $value
     * @return $this
     */
    final public function default(\DateTime|int|string $value): static
    {
        if (is_string($value)) {
            $value = strtotime($value);
        }

        $this->setDefaultValue(match (true) {
            is_int($value) && $value > 0 => date("Y-m-d", $value),
            $value instanceof \DateTime => $value->format("Y-m-d"),
            default => throw new \InvalidArgumentException('Invalid type for date value'),
        });

        return $this;
    }

    /**
     * @param DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver->value) {
            "mysql", "pgsql" => "DATE",
            default => "TEXT",
        };
    }

    /**
     * @return void
     */
    protected function attributesCallback(): void
    {
        $this->attributes->setModelsValueResolver(function (?string $value): ?\DateTime {
            return ($value) ? \DateTime::createFromFormat("Y-m-d", $value) : null;
        });

        $this->attributes->setModelsValueDissolveFn(function (?\DateTime $date): ?string {
            return $date?->format("Y-m-d");
        });
    }
}