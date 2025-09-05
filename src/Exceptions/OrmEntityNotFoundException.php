<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Exceptions;

/**
 * Represents an exception thrown when a requested ORM entity cannot be found.
 * Extends the base OrmException class to provide more specific error handling
 * related to missing ORM models.
 */
class OrmEntityNotFoundException extends OrmException
{
}

