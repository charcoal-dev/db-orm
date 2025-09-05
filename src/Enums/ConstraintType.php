<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Enums;

enum ConstraintType
{
    case ForeignKey;
    case IndexKey;
    case UniqueKey;
}