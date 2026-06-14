<?php

declare(strict_types=1);

namespace RssGrabber\Tests\Integration;

use PHPUnit\Framework\TestCase;
use AdminRepository;
use mysqli;

require_once RSSG_ROOT . '/classes/AdminRepository.php';

final class AdminRepositoryTest extends TestCase
{
    private mysqli $link;
    private AdminRepository $repo;

    protected function setUp(): void
    {
        mysqli_report(MYSQLI_REPORT_OFF);
        $host = getenv('RSSG_DB_HOST') ?: 'db';
        $conn = @mysqli_connect($host, 'root', 'root');
        if (!$conn instanceof mysqli) {
            self::markTestSkipped('Test-Datenbank (' . $host . ') nicht erreichbar.');
        }
        mysqli_query($conn, 'CREATE DATABASE IF NOT EXISTS `rss_grabber_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        mysqli_select_db($conn, 'rss_grabber_test');
        mysqli_set_charset($conn, 'utf8mb4');
        mysqli_query($conn, 'CREATE TABLE IF NOT EXISTS `admin` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(64) NOT NULL,
            `password_hash` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        mysqli_query($conn, 'TRUNCATE `admin`');

        $hash = password_hash('pw123', PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, 'INSERT INTO `admin` (username, password_hash) VALUES (?, ?)');
        self::assertNotFalse($stmt);
        $user = 'tester';
        mysqli_stmt_bind_param($stmt, 'ss', $user, $hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $this->link = $conn;
        $this->repo = new AdminRepository($conn);
    }

    protected function tearDown(): void
    {
        if (isset($this->link)) {
            mysqli_close($this->link);
        }
    }

    public function testVerifyLoginMitKorrektenDaten(): void
    {
        self::assertTrue($this->repo->verifyLogin('tester', 'pw123'));
    }

    public function testVerifyLoginMitFalschemPasswort(): void
    {
        self::assertFalse($this->repo->verifyLogin('tester', 'falsch'));
    }

    public function testVerifyLoginMitUnbekanntemBenutzer(): void
    {
        self::assertFalse($this->repo->verifyLogin('niemand', 'pw123'));
    }
}
