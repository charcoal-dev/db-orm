<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\PrimitiveType;
use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Concerns\LobSize;
use Charcoal\Database\Orm\Schema\Traits\LargeObjectSizeTrait;
use Charcoal\Database\Orm\Schema\Traits\ColumnCharsetTrait;

/**
 * Class TextColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class TextColumn extends AbstractColumn
{
    public const PrimitiveType PRIMITIVE_TYPE = PrimitiveType::STRING;
    protected LobSize $size = LobSize::DEFAULT;

    use ColumnCharsetTrait;
    use LargeObjectSizeTrait;

    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["size"] = $this->size;
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->size = $data["size"];
        unset($data["size"]);
        parent::__unserialize($data);
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver) {
            DbDriver::MYSQL => $this->size->getColumn($driver, text: true),
            default => "TEXT",
        };
    }
}
