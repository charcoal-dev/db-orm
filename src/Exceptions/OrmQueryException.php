<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Exceptions;

use Charcoal\Database\Orm\Concerns\OrmError;

/**
 * Class OrmQueryException
 * @package Charcoal\Database\Orm\Exceptions
 */
class OrmQueryException extends OrmException
{
    public function __construct(
        public readonly OrmError $ormError,
        string                   $message = "",
        ?\Throwable              $previous = null)
    {
        parent::__construct($message, $this->ormError->value, $previous);
    }
}