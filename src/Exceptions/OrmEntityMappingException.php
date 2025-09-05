<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Exceptions;

/**
 * This exception is thrown when there are errors related to entity mapping configurations
 * or interactions, such as invalid definitions, mismatched types, or unsupported features
 * encountered during the ORM processes.
 */
class OrmEntityMappingException extends OrmException
{
}