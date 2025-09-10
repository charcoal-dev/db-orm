<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\MySqlEngine;

/**
 * A builder class for managing table attributes, allowing the user to
 * define and retrieve arbitrary key-value pairs and set the MySQL storage engine.
 */
class TableAttributesBuilder
{
    public ?MySqlEngine $mysqlEngine = null;
    public bool $enforceChecks = true;

    public function __construct(
        public readonly string   $name,
        public readonly DbDriver $driver
    )
    {
    }
}
