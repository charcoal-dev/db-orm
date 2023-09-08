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
require_once "live-db.config.php";

/**
 * Class LiveDbTest
 */
class LiveDbTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Database\Exception\DbConnectionException
     */
    public function testCheckMigrations(): void
    {
        $db = $this->getDbConnection();
        $usersSchema = new \Charcoal\Tests\ORM\UsersTable();
        $migrations = $usersSchema->getMigrations($db, versionTo: 10); // $migrations[#version][#index]
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

        $this->assertEquals($createTableStmt, $migrations[0][0]);

        $alterTableStmt = "ALTER TABLE `users` " .
            "ADD COLUMN `country` char(3) CHARACTER SET ascii COLLATE ascii_general_ci default NULL " .
            "AFTER `last_name`;";

        $this->assertEquals($alterTableStmt, $migrations[7][0]);

        $usersLogsTable = new \Charcoal\Tests\ORM\UsersLogsTable();
        $logsMigrations = $usersLogsTable->getMigrations($db, versionFrom: 0, versionTo: 6);

        $createLogsTable = "CREATE TABLE IF NOT EXISTS `users_logs` (" .
            "`id` bigint UNSIGNED PRIMARY KEY auto_increment NOT NULL," .
            "`user` int UNSIGNED NOT NULL," .
            "`log` varchar(512) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL," .
            "FOREIGN KEY (`user`) REFERENCES `users`(`id`)" .
            ") ENGINE=InnoDB;";
        $this->assertIsArray($logsMigrations);
        $this->assertCount(2, $logsMigrations);
        $this->assertEquals($createLogsTable, $logsMigrations[0][0]);
    }

    /**
     * @return void
     * @throws \Charcoal\Database\Exception\DbConnectionException
     */
    public function testCheckVersioning(): void
    {
        $db = $this->getDbConnection();
        $migrations = new \Charcoal\Database\ORM\Migrations($db, versionFrom: 0, versionTo: 20);
        $migrations->includeTable(new \Charcoal\Tests\ORM\UsersTable())
            ->includeTable(new \Charcoal\Tests\ORM\UsersLogsTable());

        $versioned = $migrations->getVersionedQueries();
        $this->assertIsArray($versioned);
        $this->assertCount(2, $versioned[0]); // 2 queries in version tag 0
        $this->assertCount(1, $versioned[6]); // 1 query in very tag 6
        $this->assertCount(3, $versioned[7]); // 3 queries in version tag 7

        $queries = $migrations->getQueries();
        $this->assertIsArray($queries);
        $this->assertCount(6, $queries);
        $this->assertStringStartsWith("CREATE TABLE IF NOT EXISTS `users`", $queries[0], "Version 0 query 1");
        $this->assertStringStartsWith("CREATE TABLE IF NOT EXISTS `users_logs`", $queries[1], "Version 0 query 2");
        $this->assertStringStartsWith("ALTER TABLE `users_logs` ADD COLUMN `added_on`", $queries[2], "Version 6 query 1");
        $this->assertStringStartsWith("ALTER TABLE `users` ADD COLUMN `country`", $queries[3], "Version 7 query 1");
        $this->assertStringStartsWith("ALTER TABLE `users_logs` ADD COLUMN `ip_address`", $queries[4], "Version 7 query 2");
        $this->assertStringStartsWith("ALTER TABLE `users_logs` ADD COLUMN `baggage`", $queries[5], "Version 7 query 3");
    }

    /**
     * @return void
     * @throws \Charcoal\Database\Exception\DbConnectionException
     * @throws \Charcoal\Database\Exception\QueryExecuteException
     */
    public function testExecuteLive(): void
    {
        $db = $this->getDbConnection();
        $migrations = new \Charcoal\Database\ORM\Migrations($db, versionFrom: 0, versionTo: 20);
        $migrations->includeTable(new \Charcoal\Tests\ORM\UsersTable())
            ->includeTable(new \Charcoal\Tests\ORM\UsersLogsTable());

        $db->exec('SET foreign_key_checks=0;');
        $db->exec('DROP TABLE IF EXISTS `users`');
        $db->exec('DROP TABLE IF EXISTS `users_logs`');
        $db->exec('SET foreign_key_checks=1');

        foreach ($migrations->getQueries() as $query) {
            $db->exec($query);
        }

        $this->assertTrue(true);
    }

    /**
     * @return \Charcoal\Database\Database
     * @throws \Charcoal\Database\Exception\DbConnectionException
     */
    private function getDbConnection(): \Charcoal\Database\Database
    {
        return new \Charcoal\Database\Database(
            new \Charcoal\Database\DbCredentials(
                \Charcoal\Database\DbDriver::MYSQL,
                dbName: "charcoal_dev_test_db",
                host: CHARCOAL_DB_HOST,
                port: CHARCOAL_DB_PORT,
                username: CHARCOAL_DB_USERNAME,
                password: CHARCOAL_DB_PASSWORD
            )
        );
    }
}