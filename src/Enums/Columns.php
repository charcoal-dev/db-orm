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
enum Columns
{
    case Binary;
    case Blob;
    case Bool;
    case Buffer;
    case DateTime;
    case Decimal;
    case Double;
    case Dsv;
    case Enum;
    case Float;
    case Frame;
    case Integer;
    case String;
    case Text;

    public function getPrimitiveType(): PrimitiveType
    {
        return match ($this) {
            self::Bool, self::Integer => PrimitiveType::Int,
            self::Binary, self::Blob, self::Buffer, self::Dsv,
            self::DateTime, self::Decimal, self::Enum, self::Frame,
            self::String, self::Text => PrimitiveType::String,
            self::Double,
            self::Float => PrimitiveType::Float,
        };
    }
}