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

require_once "TestModels.php";

/**
 * Class MappingTest
 */
class MappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Database\ORM\Exception\OrmException
     */
    public function testMappings(): void
    {
        $table = new \Charcoal\Tests\ORM\UsersTable();
        $mapper = new \Charcoal\Database\ORM\Schema\ModelMapper($table);

        $user = $mapper->mapSingle($this->getUserRow());
        $this->assertInstanceOf(\Charcoal\Tests\ORM\User::class, $user);
        $this->assertEquals(801, $user->id);
        $this->assertInstanceOf(\Charcoal\Buffers\Frames\Bytes20::class, $user->checksum);
        $this->assertEquals("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\t\ntest", $user->checksum->raw());
        $this->assertIsString($user->status);
        $this->assertIsNotString($user->role);
        $this->assertNotEquals(\Charcoal\Tests\ORM\UserRole::MODERATOR, $user->role);
        $this->assertEquals(\Charcoal\Tests\ORM\UserRole::USER, $user->role);
        $this->assertIsString($user->firstName);
        $this->assertEquals("چارکول", $user->firstName);
        $this->assertIsString($user->lastName);
        $this->assertFalse(isset($user->email), "E-mail was not set therefore remains uninitialized");
        $this->assertIsInt($user->joinedOn);
        $this->assertEquals(65535, $user->joinedOn);
        $this->assertFalse(isset($user->unmapped), "Unmapped property does not exist");
    }

    /**
     * @return void
     * @throws \Charcoal\Database\ORM\Exception\OrmException
     */
    public function testUnmappedProps(): void
    {
        $table = new \Charcoal\Tests\ORM\UsersTable();
        $mapper = new \Charcoal\Database\ORM\Schema\ModelMapper($table);
        $table->modelClass = \Charcoal\Tests\ORM\User2::class;

        $user = $mapper->mapSingle($this->getUserRow());
        $this->assertInstanceOf(\Charcoal\Tests\ORM\User2::class, $user);
        $this->assertInstanceOf(\Charcoal\Buffers\Frames\Bytes20::class, $user->checksum);
        $this->assertEquals(\Charcoal\Tests\ORM\UserRole::USER, $user->role);
        $this->assertTrue(isset($user->unmapped), "Unmapped is set");
        $this->assertArrayHasKey("extra_prop_1", $user->unmapped);
        $this->assertArrayNotHasKey("extraProp1", $user->unmapped, "Unmapped keys do not have case-style altered");
        $this->assertEquals(100, $user->unmapped["extra_prop_2"]);
        $this->assertArrayHasKey("extra_prop_3", $user->unmapped);
    }

    /**
     * @return void
     * @throws \Charcoal\Database\ORM\Exception\OrmException
     * @throws \ReflectionException
     */
    public function testDissolve(): void
    {
        $table = new \Charcoal\Tests\ORM\UsersTable();
        $table->modelClass = \Charcoal\Tests\ORM\User2::class;
        $mapper = new \Charcoal\Database\ORM\Schema\ModelMapper($table);
        $user = $mapper->mapSingle($this->getUserRow());

        $this->assertInstanceOf(\Charcoal\Tests\ORM\User2::class, $user);

        $dissolveFn = new ReflectionMethod($table, "dissolveModelObject");
        /** @noinspection PhpExpressionResultUnusedInspection */
        $dissolveFn->setAccessible(true);
        $row = $dissolveFn->invoke($table, $user);

        $this->assertIsArray($row);
        $this->assertEquals($user->id, $row["id"]);
        $this->assertEquals($user->username, $row["username"]);
        $this->assertEquals($user->status, $row["status"]);
        $this->assertEquals($user->role->value, $row["role"]);
        $this->assertEquals($user->firstName, $row["first_name"]);
        $this->assertArrayNotHasKey("firstName", $row, "Keys are converted back to snake_case");
        $this->assertEquals($user->lastName, $row["last_name"]);
        $this->assertEquals($user->joinedOn, $row["joined_on"]);
        $this->assertArrayNotHasKey("firstName", $row, "Keys are converted back to snake_case");
        $this->assertArrayNotHasKey("unmapped", $row, "Only column keys are in array");
        $this->assertArrayNotHasKey("someThingElse", $row, "Only column keys are in array");
    }

    /**
     * @return array
     */
    private function getUserRow(): array
    {
        return [
            "id" => 801,
            "username" => "charcoal",
            // e-mail is deliberately not set
            "checksum" => "\t\ntest",
            "status" => "active",
            "role" => "user",
            "first_name" => "چارکول",
            "last_name" => "ڈيو",
            "country" => "UAE",
            "joined_on" => 0xffff,
            "extra_prop_1" => "this-is-not-in-map",
            "extra_prop_2" => 100,
            "extra_prop_3" => "this-neither"
        ];
    }
}
