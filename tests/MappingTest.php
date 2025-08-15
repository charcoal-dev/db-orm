<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm;

use Charcoal\Buffers\Frames\Bytes20;
use Charcoal\Database\Orm\Schema\ModelMapper;
use Charcoal\Database\Tests\Orm\Models\User;
use Charcoal\Database\Tests\Orm\Models\User2;
use Charcoal\Database\Tests\Orm\Models\UserRole;
use Charcoal\Database\Tests\Orm\Models\UsersTable;

/**
 * Class MappingTest
 */
class MappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Database\Orm\Exceptions\OrmException
     */
    public function testMappings(): void
    {
        $table = new UsersTable("users");
        $mapper = new ModelMapper($table);

        $user = $mapper->mapSingle($this->getUserRow());
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(801, $user->id);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(\Charcoal\Buffers\Frames\Bytes20::class, $user->checksum);
        $this->assertEquals("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\t\ntest", $user->checksum->raw());
        $this->assertIsString($user->status);
        $this->assertFalse($user->isDeleted);
        $this->assertTrue($user->testBool2);
        $this->assertIsNotString($user->role);
        $this->assertNotEquals(UserRole::MODERATOR, $user->role);
        $this->assertEquals(UserRole::USER, $user->role);
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
     * @throws \Charcoal\Database\Orm\Exceptions\OrmModelMapException
     * @throws \Charcoal\Database\Orm\Exceptions\OrmModelNotFoundException
     */
    public function testUnmappedProps(): void
    {
        $table = new UsersTable("users");
        $mapper = new ModelMapper($table);
        $table->modelClass = User2::class;

        $user = $mapper->mapSingle($this->getUserRow());
        $this->assertInstanceOf(User2::class, $user);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(Bytes20::class, $user->checksum);
        $this->assertEquals(UserRole::USER, $user->role);
        $this->assertTrue(isset($user->unmapped), "Unmapped is set");
        $this->assertArrayHasKey("extra_prop_1", $user->unmapped);
        $this->assertArrayNotHasKey("extraProp1", $user->unmapped, "Unmapped keys do not have case-style altered");
        $this->assertEquals(100, $user->unmapped["extra_prop_2"]);
        $this->assertArrayHasKey("extra_prop_3", $user->unmapped);
    }

    /**
     * @return void
     * @throws \Charcoal\Database\Orm\Exceptions\OrmException
     * @throws \ReflectionException
     */
    public function testDissolve(): void
    {
        $table = new UsersTable("users");
        $table->modelClass = User2::class;
        $mapper = new ModelMapper($table);
        $user = $mapper->mapSingle($this->getUserRow());

        $this->assertInstanceOf(User2::class, $user);
        $this->assertObjectHasProperty("unmapped", $user);
        $this->assertIsArray($user->unmapped);

        $user->isDeleted = true;
        $user->testBool2 = null;

        $dissolveFn = new \ReflectionMethod($table, "fromObjectToArray");
        $row = $dissolveFn->invoke($table, $user);

        $this->assertIsArray($row);
        $this->assertEquals($user->id, $row["id"]);
        $this->assertEquals($user->username, $row["username"]);
        $this->assertEquals(1, $row["is_deleted"]);
        //$this->assertEquals(0, $row["test_bool_2"]);
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
            "is_deleted" => 0,
            "test_bool_2" => 1,
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
