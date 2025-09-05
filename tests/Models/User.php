<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm\Models;

use Charcoal\Buffers\Types\Bytes20;

class User
{
    public int $id;
    public string $status;
    public UserRole $role;
    public Bytes20 $checksum;
    public bool $isDeleted;
    public ?bool $testBool2;
    public string $username;
    public string $email;
    public ?string $firstName = null;
    public ?string $lastName = null;
    public string $country;
    public int $joinedOn;
}

