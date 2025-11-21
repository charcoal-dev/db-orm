<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Enums;

use Charcoal\Contracts\Types\PrimitiveType;

/**
 * Represents a set of database column types.
 */
enum ColumnType
{
    case Binary;
    case Blob;
    case Bool;
    case Buffer;
    case Date;
    case Decimal;
    case Double;
    case Dsv;
    case Enum;
    case Float;
    case Frame;
    case Integer;
    case String;
    case Text;
    case Json;

    /**
     * Get the primitive type of the column.
     */
    public function getPrimitiveType(): PrimitiveType
    {
        return match ($this) {
            self::Bool,
            self::Integer => PrimitiveType::Int,
            self::Binary,
            self::Blob,
            self::Buffer,
            self::Date,
            self::Dsv,
            self::Decimal,
            self::Enum,
            self::Frame,
            self::String,
            self::Json,
            self::Text => PrimitiveType::String,
            self::Double,
            self::Float => PrimitiveType::Float,
        };
    }
}