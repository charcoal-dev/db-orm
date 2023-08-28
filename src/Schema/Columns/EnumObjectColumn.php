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

/**
 * Class EnumObjectColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class EnumObjectColumn extends EnumColumn
{
    /**
     * @param string $name
     * @param string $enumClass
     */
    public function __construct(string $name, private readonly string $enumClass)
    {
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function attributesCallback(): void
    {
        /** @var \BackedEnum $enumClass */
        $enumClass = $this->enumClass;
        $this->attributes->setModelsValueResolver(function ($value) use ($enumClass) {
            if (is_string($value) || is_int($value)) {
                return $enumClass::from($value);
            }

            return $value;
        });

        $this->attributes->setModelsValueDissolveFn(function ($value) {
            if ($value instanceof \BackedEnum) {
                return $value->value;
            }

            return $value;
        });
    }
}
