<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\PrimitiveType;
use Charcoal\Database\DbDriver;
use Charcoal\Database\Orm\Schema\Traits\LengthValueTrait;
use Charcoal\Database\Orm\Schema\Traits\StringValueTrait;
use Charcoal\Database\Orm\Schema\Traits\UniqueValueTrait;

/**
 * Class BinaryColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class BinaryColumn extends AbstractColumn
{
    public const PrimitiveType PRIMITIVE_TYPE = PrimitiveType::STRING;
    protected const int LENGTH_MIN = 1;
    protected const int  LENGTH_MAX = 0xffff;

    protected int $length = 255;
    protected bool $fixed = false;

    use LengthValueTrait;
    use StringValueTrait;
    use UniqueValueTrait;

    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["length"] = $this->length;
        $data["fixed"] = $this->fixed;
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->length = $data["length"];
        $this->fixed = $data["fixed"];
        unset($data["length"], $data["fixed"]);
        parent::__unserialize($data);
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => sprintf('%s(%d)', ($this->fixed ? "binary" : "varbinary"), $this->length),
            default => "BLOB"
        };
    }
}
