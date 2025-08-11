<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\PrimitiveType;
use Charcoal\Database\DbDriver;

/**
 * Class BoolColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class BoolColumn extends AbstractColumn
{
    public const PrimitiveType PRIMITIVE_TYPE = PrimitiveType::INT;

    protected function attributesCallback(): void
    {
        $this->attributes->unSigned = true;
        $this->attributes->resolveTypedValue(function (mixed $value): bool {
            return $value === 1;
        });

        $this->attributes->resolveDbValue(function (mixed $value): int {
            return is_bool($value) && $value ? 1 : 0;
        });
    }

    public function default(bool $defaultValue): static
    {
        $this->setDefaultValue($defaultValue ? 1 : 0);
        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => "tinyint",
            default => "integer",
        };
    }
}

