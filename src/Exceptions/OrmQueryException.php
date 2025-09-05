<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Exceptions;

use Charcoal\Database\Orm\Enums\OrmError;

/**
 * This exception is thrown to signal an error that occurs during the execution
 * or handling of an ORM-related query. The associated OrmError provides
 * detailed context about the specific issue encountered.
 */
class OrmQueryException extends OrmException
{
    public function __construct(
        public readonly OrmError $ormError,
        string                   $message = "",
        ?\Throwable              $previous = null
    )
    {
        parent::__construct($message, $this->ormError->value, $previous);
    }
}