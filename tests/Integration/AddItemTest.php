<?php

declare(strict_types=1);

namespace RssGrabber\Tests\Integration;

use PHPUnit\Framework\TestCase;
use mysqli;

require_once RSSG_ROOT . '/classes/function.php';

final class AddItemTest extends TestCase
{
    private mysqli $link;

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
        mysqli_query($conn, 'CREATE TABLE IF NOT EXISTS `feeds_post` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `feeds_id` int(11) NOT NULL,
            `pubDate` datetime NOT NULL,
            `link` varchar(255) NOT NULL,
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        mysqli_query($conn, 'TRUNCATE `feeds_post`');
        $this->link = $conn;
    }

    protected function tearDown(): void
    {
        if (isset($this->link)) {
            mysqli_close($this->link);
        }
    }

    public function testRssItemWirdGespeichert(): void
    {
        $rss = simplexml_load_string(
            '<rss><channel><item>'
            . '<title>PHP 8.5 News</title>'
            . '<link>https://example.com/a</link>'
            . '<description>Beschreibung</description>'
            . '<pubDate>Wed, 02 Oct 2024 13:00:00 +0000</pubDate>'
            . '</item></channel></rss>'
        );
        self::assertNotFalse($rss);

        addItem('1', $rss->channel->item[0], 1, $this->link, 1);

        $res = mysqli_query($this->link, 'SELECT title FROM `feeds_post` WHERE `feeds_id`=1');
        self::assertInstanceOf(\mysqli_result::class, $res);
        $row = mysqli_fetch_assoc($res);
        self::assertIsArray($row);
        self::assertSame('PHP 8.5 News', $row['title']);
    }

    public function testUmlauteBleibenAlsUtf8Erhalten(): void
    {
        // Kern-Regression: vor dem Fix wandelte addItem UTF-8 nach ISO-8859-1
        // und erzeugte in der utf8mb4-DB Mojibake.
        $titel = 'Schöne Grüße äöüß – Wörter';
        $rss = simplexml_load_string(
            '<rss><channel><item>'
            . '<title>' . htmlspecialchars($titel, ENT_XML1, 'UTF-8') . '</title>'
            . '<link>https://example.com/umlaut</link>'
            . '<description>Tür, Übung, Maß</description>'
            . '<pubDate>Wed, 02 Oct 2024 13:00:00 +0000</pubDate>'
            . '</item></channel></rss>'
        );
        self::assertNotFalse($rss);

        addItem('1', $rss->channel->item[0], 1, $this->link, 1);

        $res = mysqli_query($this->link, 'SELECT title, description FROM `feeds_post` WHERE `feeds_id`=1');
        self::assertInstanceOf(\mysqli_result::class, $res);
        $row = mysqli_fetch_assoc($res);
        self::assertIsArray($row);
        self::assertSame($titel, $row['title']);
        self::assertSame('Tür, Übung, Maß', $row['description']);
        self::assertTrue(mb_check_encoding((string)$row['title'], 'UTF-8'));
        self::assertTrue(mb_check_encoding((string)$row['description'], 'UTF-8'));
    }

    public function testDuplikatWirdNichtDoppeltGespeichert(): void
    {
        $xml = '<rss><channel><item>'
            . '<title>Einmalig</title>'
            . '<link>https://example.com/dup</link>'
            . '<description>x</description>'
            . '<pubDate>Wed, 02 Oct 2024 13:00:00 +0000</pubDate>'
            . '</item></channel></rss>';

        $a = simplexml_load_string($xml);
        $b = simplexml_load_string($xml);
        self::assertNotFalse($a);
        self::assertNotFalse($b);

        addItem('1', $a->channel->item[0], 5, $this->link, 1);
        addItem('1', $b->channel->item[0], 5, $this->link, 1);

        $res = mysqli_query($this->link, 'SELECT COUNT(*) AS c FROM `feeds_post` WHERE `feeds_id`=5');
        self::assertInstanceOf(\mysqli_result::class, $res);
        $row = mysqli_fetch_assoc($res);
        self::assertIsArray($row);
        self::assertSame(1, (int)$row['c']);
    }

    public function testAtomEintragWirdGespeichert(): void
    {
        $atom = simplexml_load_string(
            '<feed xmlns="http://www.w3.org/2005/Atom"><entry>'
            . '<title>Atom Ümlaut Eintrag</title>'
            . '<link href="https://example.com/atom"/>'
            . '<published>2024-10-02T13:00:00Z</published>'
            . '<content>Inhalt äöü</content>'
            . '</entry></feed>'
        );
        self::assertNotFalse($atom);

        addItem('1', $atom->entry[0], 9, $this->link, 2);

        $res = mysqli_query($this->link, 'SELECT title, link FROM `feeds_post` WHERE `feeds_id`=9');
        self::assertInstanceOf(\mysqli_result::class, $res);
        $row = mysqli_fetch_assoc($res);
        self::assertIsArray($row);
        self::assertSame('Atom Ümlaut Eintrag', $row['title']);
        self::assertSame('https://example.com/atom', $row['link']);
    }
}
