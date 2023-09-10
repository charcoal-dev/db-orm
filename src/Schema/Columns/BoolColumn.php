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
 * Class BoolColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class BoolColumn extends AbstractColumn
{
    public const PRIMITIVE_TYPE = "integer";

    /**
     * @return void
     */
    protected function attributesCallback(): void
    {
        $this->attributes->unSigned = true;
        $this->attributes->setModelsValueResolver(function (mixed $value): bool {
            return $value === 1;
        });

        $this->attributes->setModelsValueDissolveFn(function (mixed $value): int {
            return is_bool($value) && $value ? 1 : 0;
        });
    }

    /**
     * @param bool $defaultValue
     * @return $this
     */
    public function default(bool $defaultValue): static
    {
        $this->setDefaultValue($defaultValue ? 1 : 0);
        return $this;
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver->value) {
            "mysql" => "tinyint",
            default => "integer",
        };
    }
}

