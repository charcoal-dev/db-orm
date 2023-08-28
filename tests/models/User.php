<?php
/*
 * This file is a part of "charcoal-dev/db-orm" package.
 * https://github.com/charcoal-dev/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/db-orm/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Tests\ORM;

/**
 * Class User
 * @package Charcoal\Tests\ORM
 */
class User
{
    public int $id;
    public string $status;
    public UserRole $userRole;
    public string $username;
    public string $email;
    public ?string $firstName = null;
    public ?string $lastName = null;
    public int $joinedOn;

}

