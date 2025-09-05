<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Snapshot;

use Charcoal\Database\Orm\Enums\MySqlEngine;

/**
 * Snapshot of table attributes.
 */
final readonly class TableAttributes
{
    public array $arbitrary;

    public function __construct(
        public ?MySqlEngine $mySqlEngine,
    )
    {
        $this->arbitrary = [];
    }
}