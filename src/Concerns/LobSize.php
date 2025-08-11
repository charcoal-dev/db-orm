<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Concerns;

use Charcoal\Database\DbDriver;

/**
 * Class LobSize
 * @package Charcoal\Database\Orm\Concerns
 */
enum LobSize: string
{
    case TINY = "tiny";
    case DEFAULT = "_";
    case MEDIUM = "medium";
    case LONG = "long";

    /**
     * @param DbDriver $dbDriver
     * @param bool $text
     * @return string
     */
    public function getColumn(DbDriver $dbDriver, bool $text): string
    {
        /** @noinspection PhpUnusedMatchConditionInspection */
        return match ($dbDriver) {
            DbDriver::MYSQL => match ($this) {
                    self::TINY => "TINY",
                    self::MEDIUM => "MEDIUM",
                    self::LONG => "LONG",
                    default => "",
                } . ($text ? "TEXT" : "BLOB"),

            DbDriver::SQLITE => $text ? "TEXT" : "BLOB",
            DbDriver::PGSQL => $text ? "TEXT" : "BYTEA",

            default => throw new \RuntimeException("Unsupported database driver: " . $dbDriver->value)
        };
    }
}