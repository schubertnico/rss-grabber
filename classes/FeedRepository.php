<?php
/**
 * -----------------------------------------
 * RSS Grabber free v2.0
 * -----------------------------------------
 * Datenzugriff fuer Feeds und Feed-Beitraege. Kapselt alle SQL-Operationen
 * (Prepared Statements fuer Eingaben), damit die Controller schlank und die
 * Logik testbar bleibt.
 *
 * @version free v2.0 (PHP 8.5)
 */
class FeedRepository
{
    public function __construct(private mysqli $link)
    {
    }

    /**
     * Alle Feeds (fuer die Verwaltung).
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $result = mysqli_query($this->link, "SELECT * FROM `feeds`");
        if (!$result instanceof mysqli_result) {
            return [];
        }
        /** @var list<array<string, mixed>> $rows */
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return $rows;
    }

    /**
     * Feed-Metadaten je feeds_id fuer die Beitragsanzeige.
     *
     * @return array<int, array{url: string, name: string}>
     */
    public function feedMap(): array
    {
        $result = mysqli_query($this->link, "SELECT id, url FROM `feeds`");
        if (!$result instanceof mysqli_result) {
            return [];
        }
        $map = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $url = (string)$row['url'];
            $map[(int)$row['id']] = ['url' => $url, 'name' => rssg_feed_name($url)];
        }
        return $map;
    }

    /**
     * Einen Feed anhand der ID laden.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = mysqli_prepare($this->link, "SELECT id, url, feed_url, `check`, last_check, last_status FROM `feeds` WHERE `id` = ? LIMIT 1");
        if ($stmt === false) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = ($result instanceof mysqli_result) ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return is_array($row) ? $row : null;
    }

    public function existsByFeedUrl(string $feedUrl): bool
    {
        $stmt = mysqli_prepare($this->link, "SELECT id FROM `feeds` WHERE `feed_url` = ? LIMIT 1");
        if ($stmt === false) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 's', $feedUrl);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        return $exists;
    }

    public function add(string $feedUrl, string $url): bool
    {
        $stmt = mysqli_prepare($this->link, "INSERT INTO `feeds` (`feed_url`,`url`,`check`,`last_status`,`last_check`) VALUES (?, ?, 1, 'k.a.', 0)");
        if ($stmt === false) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ss', $feedUrl, $url);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function update(int $id, int $check, string $feedUrl, string $url): bool
    {
        $stmt = mysqli_prepare($this->link, "UPDATE `feeds` SET `check` = ?, `feed_url` = ?, `url` = ? WHERE `id` = ? LIMIT 1");
        if ($stmt === false) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'issi', $check, $feedUrl, $url, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function delete(int $id): bool
    {
        $stmt = mysqli_prepare($this->link, "DELETE FROM `feeds` WHERE `id` = ? LIMIT 1");
        if ($stmt === false) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'i', $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * Neueste Beitraege der angegebenen Feeds (absteigend nach pubDate).
     *
     * @param list<int> $feedIds
     * @return list<array<string, mixed>>
     */
    public function latestPosts(array $feedIds, int $limit, int $offset = 0): array
    {
        $inList = $this->intInList($feedIds);
        $limit = max(0, $limit);
        $offset = max(0, $offset);
        $sql = "SELECT * FROM `feeds_post` WHERE `feeds_id` IN ($inList) ORDER BY `pubDate` DESC LIMIT $offset, $limit";
        $result = mysqli_query($this->link, $sql);
        if (!$result instanceof mysqli_result) {
            return [];
        }
        /** @var list<array<string, mixed>> $rows */
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return $rows;
    }

    /**
     * Gesamtzahl der Beitraege der angegebenen Feeds.
     *
     * @param list<int> $feedIds
     */
    public function countPosts(array $feedIds): int
    {
        $inList = $this->intInList($feedIds);
        $result = mysqli_query($this->link, "SELECT COUNT(*) AS c FROM `feeds_post` WHERE `feeds_id` IN ($inList)");
        if (!$result instanceof mysqli_result) {
            return 0;
        }
        $row = mysqli_fetch_assoc($result);
        return is_array($row) ? (int)$row['c'] : 0;
    }

    public function countActive(): int
    {
        $result = mysqli_query($this->link, "SELECT COUNT(*) AS c FROM `feeds` WHERE `check` = 1");
        if (!$result instanceof mysqli_result) {
            return 0;
        }
        $row = mysqli_fetch_assoc($result);
        return is_array($row) ? (int)$row['c'] : 0;
    }

    public function countDue(int $now): int
    {
        $stmt = mysqli_prepare($this->link, "SELECT COUNT(*) AS c FROM `feeds` WHERE `check` = 1 AND `last_check` < ?");
        if ($stmt === false) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, 'i', $now);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = ($result instanceof mysqli_result) ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return is_array($row) ? (int)$row['c'] : 0;
    }

    /**
     * Faellige Feeds (aktiv und ueberfaellig), begrenzt.
     *
     * @return list<array{id: int, feed_url: string}>
     */
    public function dueFeeds(int $now, int $limit): array
    {
        $limit = max(1, $limit);
        $stmt = mysqli_prepare($this->link, "SELECT id, feed_url FROM `feeds` WHERE `check` = 1 AND `last_check` < ? LIMIT $limit");
        if ($stmt === false) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'i', $now);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!$result instanceof mysqli_result) {
            mysqli_stmt_close($stmt);
            return [];
        }
        $feeds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $feeds[] = ['id' => (int)$row['id'], 'feed_url' => (string)$row['feed_url']];
        }
        mysqli_stmt_close($stmt);
        return $feeds;
    }

    public function markChecked(int $id, int $nextCheck, string $status): void
    {
        $stmt = mysqli_prepare($this->link, "UPDATE `feeds` SET `last_check` = ?, `last_status` = ? WHERE `id` = ? LIMIT 1");
        if ($stmt === false) {
            return;
        }
        mysqli_stmt_bind_param($stmt, 'isi', $nextCheck, $status, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /**
     * Baut aus einer ID-Liste eine sichere, kommaseparierte Integer-Liste
     * (leer -> "0", trifft nichts).
     *
     * @param list<int> $feedIds
     */
    private function intInList(array $feedIds): string
    {
        $ids = array_map('intval', $feedIds);
        return $ids === [] ? '0' : implode(',', $ids);
    }
}
