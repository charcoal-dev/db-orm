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
 * Class MigrationsTest
 */
class MigrationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Database\Exception\DbConnectionException
     */
    public function testCreateTable(): void
    {
        $db = new \Charcoal\Database\Database(
            new \Charcoal\Database\DbCredentials(
                \Charcoal\Database\DbDriver::MYSQL,
                dbName: "charcoal_dev_test_db",
                host: "localhost",
                port: 7101,
                username: "root",
            )
        );

        $usersSchema = new \Charcoal\Tests\ORM\UsersTable();
        $migrations = $usersSchema->getMigrations($db, versionTo: 2);
        $this->assertIsArray($migrations);
        $this->assertCount(2, $migrations);

        $createTableStmt = "CREATE TABLE IF NOT EXISTS `users` (" .
            "`id` int UNSIGNED PRIMARY KEY auto_increment NOT NULL," .
            "`status` enum('active','frozen','disabled') NOT NULL default 'active'," .
            "`role` enum('user','mod') NOT NULL default 'user'," .
            "`checksum` binary(20) NOT NULL," .
            "`username` varchar(16) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL," .
            "`email` varchar(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL," .
            "`first_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci default NULL," .
            "`last_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci default NULL," .
            "`joined_on` int UNSIGNED NOT NULL," .
            "UNIQUE KEY (`username`)," .
            "UNIQUE KEY (`email`)" .
            ") ENGINE=InnoDB;";

        $this->assertEquals($createTableStmt, $migrations[0]);

        $alterTableStmt = "ALTER TABLE `users` " .
            "ADD COLUMN `country` char(3) CHARACTER SET ascii COLLATE ascii_general_ci default NULL " .
            "AFTER `last_name`;";

        $this->assertEquals($alterTableStmt, $migrations[1]);
    }
}