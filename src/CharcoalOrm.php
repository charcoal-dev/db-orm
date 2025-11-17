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
    /**
     * Global switch to silently discard CHECK REGEXP for SQLite.
     * SQLite3 support can be enabled using Charcoal\Database\Support\SQLite3::enableRegExpLike() method.
     * @see \Charcoal\Database\Support\SQLite3::enableRegExpLike()
     */
    public static bool $sqLiteEmitRegExp = true;

    /** CHECK RegExp behavior control */
    public static bool $constraintRegExpEscapeQuotes = true;
    public static bool $constraintRegExpTrimSlashes = true;
    public static bool $constraintRegExpEscapeSlashes = true;

    /** language=RegExp Global RegExp for table, column and constraint names  */
    public const string NAME_REGEXP = "/^[a-z][a-z0-9_]{1,62}$/";

    /** @var string[] Comprehensive index of SQL reserved keywords */
    public const array RESERVED_KEYWORDS = [
        "all", "and", "any", "as", "asc", "between", "by", "case", "check", "collate", "constraint", "create",
        "current_date", "current_time", "current_timestamp", "current_user", "default", "delete", "desc", "distinct",
        "drop", "else", "end", "exists", "false", "from", "group", "having", "in", "index", "inner", "insert",
        "into", "is", "join", "key", "left", "like", "limit", "not", "null", "offset", "on", "or", "order", "outer",
        "primary", "references", "right", "select", "set", "table", "then", "time", "timestamp", "true", "unique",
        "update", "user", "using", "values", "view", "when", "where", "with", "without"
    ];

    /** @var string[] Comprehensive index of DSV delimiters */
    public const array DSV_DELIMITERS = [" ", ",", "\t", "|", ";", ":"];

    /**
     * Checks if the given argument is a reserved SQL keyword.
     */
    public static function isReserved(string $word): bool
    {
        return in_array(strtolower($word), self::RESERVED_KEYWORDS, true);
    }

    /**
     * Returns normalized RegExp to be used in CHECK constraints.
     */
    public static function getConstraintRegExp(string $column, string $regExp): array
    {
        if (!$regExp || @preg_match($regExp, "") === false) {
            throw new \InvalidArgumentException("Bad RegExp pattern for column: " . $column);
        }

        $delimiter = $regExp[0];
        preg_match("/" . preg_quote($delimiter, "/") . "[a-z]*\z/", $regExp, $flags);
        $flags = substr($flags[0] ?? "\0", 1);
        if ($flags && !preg_match("/\A[im]*\z/", $flags)) {
            throw new \InvalidArgumentException("RegExp flags must be a combination of i or m");
        }

        if (self::$constraintRegExpTrimSlashes) $regExp = substr($regExp, 1, -1 * (strlen($flags) + 1));
        if (self::$constraintRegExpEscapeQuotes) $regExp = str_replace("'", "''", $regExp);
        if (self::$constraintRegExpEscapeSlashes) $regExp = str_replace("\\", "\\\\", $regExp);
        return [$regExp, $flags ?: null];
    }
}