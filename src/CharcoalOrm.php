<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm;

/**
 * Charcoal Orm global configuration and constants.
 */
final class CharcoalOrm
{
    /** @var bool Global switch to silently discard CHECK REGEXP for SQLite */
    public static bool $sqLiteEmitRegExp = false;

    /** @var string[] Comprehensive index of SQL reserved keywords */
    public const array RESERVED_KEYWORDS = [
        "all", "and", "any", "as", "asc", "between", "by", "case", "check", "collate", "constraint", "create",
        "current_date", "current_time", "current_timestamp", "current_user", "default", "delete", "desc", "distinct",
        "drop", "else", "end", "exists", "false", "from", "group", "having", "in", "index", "inner", "insert",
        "into", "is", "join", "key", "left", "like", "limit", "not", "null", "offset", "on", "or", "order", "outer",
        "primary", "references", "right", "select", "set", "table", "then", "time", "timestamp", "true", "unique",
        "update", "user", "using", "values", "view", "when", "where", "with", "without"
    ];

    /**
     * Checks if the given argument is a reserved SQL keyword.
     */
    public static function isReserved(string $word): bool
    {
        return in_array(strtolower($word), self::RESERVED_KEYWORDS, true);
    }
}