<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Exceptions;

use Charcoal\Database\Exceptions\DatabaseException;

/**
 * Represents an exception specific to issues encountered within the ORM (Object-Relational Mapping) system.
 * Extends the base DatabaseException class to indicate that the error originates from ORM operations.
 */
class OrmException extends DatabaseException
{
}
