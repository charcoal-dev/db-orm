<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Snapshot;

use Charcoal\Database\Orm\Enums\ConstraintType;

/**
 * Snapshot of constraint attributes.
 */
final readonly class ConstraintSnapshot
{
    public function __construct(
        public string         $name,
        public ConstraintType $type,
        public string         $schemaSql,
        public array          $columns,
        public ?string        $table = null,
        public ?string        $database = null,
    )
    {
    }
}