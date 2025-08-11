<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

/**
 * Class EnumObjectColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 * @property class-string $enumClass
 */
class EnumObjectColumn extends EnumColumn
{
    public function __construct(string $name, private readonly string $enumClass)
    {
        parent::__construct($name);
    }

    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["enumClass"] = $this->enumClass;
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->enumClass = $data["enumClass"];
        unset($data["enumClass"]);
        parent::__unserialize($data);
    }

    protected function attributesCallback(): void
    {
        /** @var class-string $enumClass */
        $enumClass = $this->enumClass;
        $this->attributes->resolveTypedValue(function ($value) use ($enumClass) {
            if (is_string($value) || is_int($value)) {
                return $enumClass::from($value);
            }

            return $value;
        });

        $this->attributes->resolveDbValue(function ($value) {
            if ($value instanceof \BackedEnum) {
                return $value->value;
            }

            return $value;
        });
    }
}
