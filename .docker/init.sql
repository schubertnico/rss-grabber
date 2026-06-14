-- ----------------------------------------------------------------
-- RSS Grabber - DB-Init-Skript (Docker)
-- Wird beim ersten Start des db-Containers automatisch ausgefuehrt
-- (docker-entrypoint-initdb.d). Entspricht der Installationsroutine
-- aus install/index.php.
-- ----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `feeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feed_url` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `check` int(2) NOT NULL,
  `last_check` int(20) NOT NULL,
  `last_status` varchar(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `feeds_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feeds_id` int(11) NOT NULL,
  `pubDate` datetime NOT NULL,
  `link` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

DELETE FROM `feeds`;
INSERT INTO `feeds` (`id`, `feed_url`, `url`, `check`, `last_check`, `last_status`) VALUES
(1, 'https://www.php-space.info/feed.xml', 'https://www.php-space.info/php/space/news.php', 1, 0, ''),
(2, 'https://www.php-space.info/script_feed.xml', 'https://www.php-space.info/scripte/', 1, 0, ''),
(3, 'https://www.php-space.info/tutorial_feed.xml', 'https://www.php-space.info/php-tutorials/', 1, 0, '');
