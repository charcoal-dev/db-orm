<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Tests\Orm;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Migrations;
use Charcoal\Database\Tests\Orm\Models\UsersTable;
use PHPUnit\Framework\TestCase;

final class TableBuilderTest extends TestCase
{
    public function testBuilderSchemaMySQL(): void
    {
        $usersTable = new UsersTable("users", DbDriver::MYSQL);
        $schema = Migrations::createTable($usersTable, true);

        $expected = [
            "CREATE TABLE IF NOT EXISTS users (",
            "id smallint UNSIGNED PRIMARY KEY auto_increment NOT NULL CHECK (id BETWEEN 1 AND 10000),",
            "status enum('active','frozen','disabled') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default 'active',",
            "role enum('user','mod') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL default 'user',",
            "is_deleted tinyint UNSIGNED NOT NULL default 0 CHECK (is_deleted IN (0,1)),",
            "test_bool_2 tinyint UNSIGNED NOT NULL CHECK (test_bool_2 IN (0,1)),",
            "checksum binary(20) NOT NULL CHECK (OCTET_LENGTH(checksum) = 20),",
            "username varchar(16) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,",
            "email varchar(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,",
            "first_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci default NULL,",
            "last_name varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci default NULL,",
            "country char(3) CHARACTER SET ascii COLLATE ascii_general_ci default NULL CHECK (CHAR_LENGTH(country) = 3),",
            "joined_on int UNSIGNED NOT NULL,",
            "UNIQUE KEY (username),",
            "UNIQUE KEY (email)",
            ") ENGINE=InnoDB;"
        ];

        $this->assertSame($expected, $schema);
    }

    public function testBuilderSchemaPgSQL(): void
    {
        $usersTable = new UsersTable("users", DbDriver::PGSQL);
        $schema = Migrations::createTable($usersTable, true);

        $expected = [
            "CREATE TABLE IF NOT EXISTS users (",
            "id integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY NOT NULL CHECK (id BETWEEN 1 AND 10000),",
            "status TEXT CHECK(status in ('active','frozen','disabled')) NOT NULL default 'active',",
            "role TEXT CHECK(role in ('user','mod')) NOT NULL default 'user',",
            "is_deleted integer NOT NULL default 0 CHECK (is_deleted IN (0,1)),",
            "test_bool_2 integer NOT NULL CHECK (test_bool_2 IN (0,1)),",
            "checksum BYTEA NOT NULL CHECK (OCTET_LENGTH(checksum) = 20),",
            "username varchar(16) UNIQUE NOT NULL,",
            "email varchar(32) UNIQUE NOT NULL,",
            "first_name varchar(32) default NULL,",
            "last_name varchar(32) default NULL,",
            "country char(3) default NULL CHECK (CHAR_LENGTH(country) = 3),",
            "joined_on integer NOT NULL",
            ");"
        ];

        $this->assertSame($expected, $schema);
    }

    public function testBuilderSchemaSQLite(): void
    {
        $usersTable = new UsersTable("users", DbDriver::SQLITE);
        $schema = Migrations::createTable($usersTable, true);

        $expected = [
            "CREATE TABLE IF NOT EXISTS users (",
            "id integer PRIMARY KEY AUTOINCREMENT NOT NULL CHECK (id BETWEEN 1 AND 10000),",
            "status TEXT CHECK(status in ('active','frozen','disabled')) NOT NULL default 'active',",
            "role TEXT CHECK(role in ('user','mod')) NOT NULL default 'user',",
            "is_deleted integer NOT NULL default 0 CHECK (is_deleted IN (0,1)),",
            "test_bool_2 integer NOT NULL CHECK (test_bool_2 IN (0,1)),",
            "checksum BLOB NOT NULL CHECK (LENGTH(checksum) = 20),",
            "username TEXT UNIQUE NOT NULL,",
            "email TEXT UNIQUE NOT NULL,",
            "first_name TEXT default NULL,",
            "last_name TEXT default NULL,",
            "country TEXT default NULL CHECK (LENGTH(country) = 3),",
            "joined_on integer NOT NULL",
            ");"
        ];

        $this->assertSame($expected, $schema);
    }
}
