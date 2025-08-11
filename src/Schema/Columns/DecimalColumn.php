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
 * Class DecimalColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class DecimalColumn extends AbstractColumn
{
    public const PrimitiveType PRIMITIVE_TYPE = PrimitiveType::STRING;
    protected const int MAX_DIGITS = 65;
    protected const int MAX_SCALE = 30;

    protected int $digits = 0;
    protected int $scale = 0;

    use NumericValueTrait;
    use PrecisionValueTrait;

    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["digits"] = $this->digits;
        $data["scale"] = $this->scale;
        return $data;
    }


    public function __unserialize(array $data): void
    {
        $this->digits = $data["digits"];
        $this->scale = $data["scale"];
        unset($data["digits"], $data["scale"]);
        parent::__unserialize($data);
    }

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->setDefaultValue("0");
    }

    public function default(string $value = "0"): static
    {
        if (!preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Bad default decimal value for col "%s"',
                $this->attributes->name));
        }

        $this->setDefaultValue($value);
        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => sprintf("decimal(%d,%d)", $this->digits, $this->scale),
            DbDriver::PGSQL => "REAL",
            default => null,
        };
    }
}
