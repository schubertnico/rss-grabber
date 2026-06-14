<?php

declare(strict_types=1);

namespace RssGrabber\Tests\Integration;

use PHPUnit\Framework\TestCase;
use FeedRepository;
use mysqli;

require_once RSSG_ROOT . '/classes/function.php';
require_once RSSG_ROOT . '/classes/FeedRepository.php';

final class FeedRepositoryTest extends TestCase
{
    private mysqli $link;
    private FeedRepository $repo;

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
        mysqli_query($conn, 'CREATE TABLE IF NOT EXISTS `feeds` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `feed_url` varchar(255) NOT NULL,
            `url` varchar(255) NOT NULL,
            `check` int(2) NOT NULL,
            `last_check` int(20) NOT NULL,
            `last_status` varchar(15) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        mysqli_query($conn, 'CREATE TABLE IF NOT EXISTS `feeds_post` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `feeds_id` int(11) NOT NULL,
            `pubDate` datetime NOT NULL,
            `link` varchar(255) NOT NULL,
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        mysqli_query($conn, 'TRUNCATE `feeds`');
        mysqli_query($conn, 'TRUNCATE `feeds_post`');
        $this->link = $conn;
        $this->repo = new FeedRepository($conn);
    }

    protected function tearDown(): void
    {
        if (isset($this->link)) {
            mysqli_close($this->link);
        }
    }

    public function testAddUndAll(): void
    {
        self::assertTrue($this->repo->add('https://a.example/feed.xml', 'https://a.example/'));
        self::assertTrue($this->repo->add('https://b.example/feed.xml', 'https://b.example/'));
        $all = $this->repo->all();
        self::assertCount(2, $all);
    }

    public function testExistsByFeedUrl(): void
    {
        $this->repo->add('https://c.example/feed.xml', 'https://c.example/');
        self::assertTrue($this->repo->existsByFeedUrl('https://c.example/feed.xml'));
        self::assertFalse($this->repo->existsByFeedUrl('https://nicht-da.example/feed.xml'));
    }

    public function testFindUpdateDelete(): void
    {
        $this->repo->add('https://d.example/feed.xml', 'https://d.example/');
        $id = (int)$this->repo->all()[0]['id'];

        $row = $this->repo->find($id);
        self::assertIsArray($row);
        self::assertSame('https://d.example/feed.xml', $row['feed_url']);

        self::assertTrue($this->repo->update($id, 2, 'https://d2.example/feed.xml', 'https://d2.example/'));
        $row = $this->repo->find($id);
        self::assertIsArray($row);
        self::assertSame('https://d2.example/feed.xml', $row['feed_url']);
        self::assertSame(2, (int)$row['check']);

        self::assertTrue($this->repo->delete($id));
        self::assertNull($this->repo->find($id));
        self::assertSame([], $this->repo->all());
    }

    public function testFeedMap(): void
    {
        $this->repo->add('https://www.example.org/feed.xml', 'https://www.example.org/blog');
        $map = $this->repo->feedMap();
        self::assertCount(1, $map);
        $entry = reset($map);
        self::assertSame('example.org', $entry['name']);
        self::assertSame('https://www.example.org/blog', $entry['url']);
    }

    public function testLatestPostsUndCount(): void
    {
        $this->repo->add('https://e.example/feed.xml', 'https://e.example/');
        $feedId = (int)$this->repo->all()[0]['id'];

        for ($n = 1; $n <= 3; $n++) {
            mysqli_query(
                $this->link,
                sprintf(
                    "INSERT INTO `feeds_post` (feeds_id, pubDate, link, title, description) VALUES (%d, '2024-10-0%d 12:00:00', 'https://e.example/%d', 'Titel %d', 'x')",
                    $feedId,
                    $n,
                    $n,
                    $n
                )
            );
        }

        self::assertSame(3, $this->repo->countPosts([$feedId]));

        $latest = $this->repo->latestPosts([$feedId], 2, 0);
        self::assertCount(2, $latest);
        // absteigend nach pubDate -> Titel 3 zuerst
        self::assertSame('Titel 3', $latest[0]['title']);

        $page2 = $this->repo->latestPosts([$feedId], 2, 2);
        self::assertCount(1, $page2);
        self::assertSame('Titel 1', $page2[0]['title']);

        // leere ID-Liste -> keine Treffer, kein Fehler
        self::assertSame([], $this->repo->latestPosts([], 10, 0));
        self::assertSame(0, $this->repo->countPosts([]));
    }

    public function testSyncQueries(): void
    {
        $this->repo->add('https://f.example/feed.xml', 'https://f.example/');
        $this->repo->add('https://g.example/feed.xml', 'https://g.example/');
        $now = 2_000_000_000;

        self::assertSame(2, $this->repo->countActive());
        self::assertSame(2, $this->repo->countDue($now));

        $due = $this->repo->dueFeeds($now, 10);
        self::assertCount(2, $due);
        self::assertIsInt($due[0]['id']);

        $this->repo->markChecked($due[0]['id'], $now + 3600, 'erfolgreich');
        self::assertSame(1, $this->repo->countDue($now));
    }
}
