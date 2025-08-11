<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\PrimitiveType;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Schema\Traits\NumericValueTrait;
use Charcoal\Database\Orm\Schema\Traits\PrecisionValueTrait;

/**
 * Class FloatColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class FloatColumn extends AbstractColumn
{
    public const PrimitiveType PRIMITIVE_TYPE = PrimitiveType::FLOAT;

    protected const int MAX_DIGITS = 65;
    protected const int MAX_SCALE = 30;

    protected string $type;
    private int $digits = 10;
    private int $scale = 0;

    use NumericValueTrait;
    use PrecisionValueTrait;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->type = "float";
        $this->setDefaultValue("0");
    }

    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["type"] = $this->type;
        $data["digits"] = $this->digits;
        $data["scale"] = $this->scale;
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->type = $data["type"];
        $this->digits = $data["digits"];
        $this->scale = $data["scale"];
        unset($data["type"], $data["digits"], $data["scale"]);
        parent::__unserialize($data);
    }

    public function default(float|int $value = 0): static
    {
        $this->setDefaultValue($value);
        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => sprintf("%s(%d,%d)", $this->type, $this->digits, $this->scale),
            DbDriver::PGSQL => "REAL",
            default => null,
        };
    }
}
