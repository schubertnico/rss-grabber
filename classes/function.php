<?php
/**
 * -----------------------------------------
 * RSS Grabber free v2.0 - 11.12.2022
 * -----------------------------------------
 * @copyright Copyright 2011, Schubertmedia/Nico Schubert
 * @link http://www.php-space.info/rss-grabber/ - Dokumentation und Informationen rund um das PHP Script.
 * @version free v2.0 (PHP 8.5)
 * @abstract
 * Das Script darf kostenlos verwendet werden. Es müssen aber alle Copyright Hinweise erhalten bleiben.
 * Für einen einmaligen Betrag von 9,95 EUR erhalten Sie die Premium-Version. In der Premium-Version sind keine
 * sichtbaren Copyright Hinweise mehr enthalten. Dadurch unterstützen Sie die Weiterentwicklung und würdigen diese Arbeit.
 */
if (function_exists("rssg_e") === false) {
    /**
     * HTML-Escaping fuer die sichere Ausgabe von DB-/Benutzerinhalten (UTF-8).
     */
    function rssg_e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (function_exists("rssg_safe_url") === false) {
    /**
     * Gibt eine fuer href sichere URL zurueck: nur http/https, sonst '#'.
     * Das Ergebnis ist HTML-escaped.
     */
    function rssg_safe_url(?string $url): string
    {
        $url = trim($url ?? '');
        if ($url === '' || preg_match('#^https?://#i', $url) !== 1) {
            return '#';
        }
        return rssg_e($url);
    }
}
if (function_exists("limitch") === false) {
    function limitch(?string $value, int $lenght): string
    {
        $value = $value ?? '';
        // mb_*-Funktionen: kürzt zeichen- statt byteweise und zerschneidet so
        // keine Umlaute (ä ö ü ß) mitten im Multibyte-Zeichen.
        if (mb_strlen($value, 'UTF-8') >= $lenght) {
            $limited = mb_substr($value, 0, $lenght, 'UTF-8') . "...";
            return stripslashes($limited);
        }
        return stripslashes($value);
    }
}
if (function_exists("rssg_render_feed_post") === false) {
    /**
     * Rendert einen Feed-Beitrag als HTML-Fragment (vollständig escaped).
     *
     * @param array<string, mixed> $row   Zeile aus der Tabelle feeds_post
     * @param array<string, array{url?: string, name?: string}> $feeds Feed-Metadaten je feeds_id
     */
    function rssg_render_feed_post(array $row, array $feeds, int $maxDesc): string
    {
        $link = (string)($row['link'] ?? '');
        $title = (string)($row['title'] ?? '');
        $feedsId = (string)($row['feeds_id'] ?? '');
        $pubDate = (string)($row['pubDate'] ?? '');
        $description = (string)($row['description'] ?? '');
        $feedUrl = (string)($feeds[$feedsId]['url'] ?? '#');
        $feedName = (string)($feeds[$feedsId]['name'] ?? 'unbekannt');

        $html  = '<a href="' . rssg_safe_url($link) . '" target="_blank" class="beitrag_title" title="' . rssg_e($title) . '">' . rssg_e($title) . '</a><br>';
        $html .= '<div class="beitrag_pubDate">Geschrieben von <a href="' . rssg_safe_url($feedUrl) . '" target="_blank">' . rssg_e($feedName) . '</a> am ' . rssg_e(date_mysql2german($pubDate)) . '</div>';
        $html .= '<div class="beitrag_description">' . rssg_e(limitch(strip_tags($description), $maxDesc)) . '</div>';
        $html .= '<div class="beitrag_link"><a href="' . rssg_safe_url($link) . '" target="_blank" class="beitrag_link">' . rssg_e(limitch($link, 95)) . '</a></div><br><br>';
        return $html;
    }
}
if (function_exists("date_mysql2german") === false) {
    function date_mysql2german(string $pubDate = '0000-00-00 00:00:00'): string
    {
        if ($pubDate === "") {
            $pubDate = '0000-00-00 00:00:00';
        }
        // Eingabeformat validieren, damit explode/list-Destructuring unter PHP 8
        // keine "Undefined array key"-Warnungen erzeugt.
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $pubDate) !== 1) {
            $pubDate = '0000-00-00 00:00:00';
        }
        [$datum, $zeit] = explode(" ", $pubDate);
        [$jahr, $monat, $tag] = explode("-", $datum);
        [$stunde, $min, $sec] = explode(":", $zeit);
        $tage = ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"];
        $timestamp = mktime((int)$stunde, (int)$min, (int)$sec, (int)$monat, (int)$tag, (int)$jahr);
        if ($timestamp === false) {
            $timestamp = time();
        }
        return sprintf(
            "%s den %02d.%02d.%04d um %02d:%02d:%02d Uhr",
            $tage[(int)date("w", $timestamp)],
            (int)$tag,
            (int)$monat,
            (int)$jahr,
            (int)$stunde,
            (int)$min,
            (int)$sec
        );
    }
}
if (function_exists("rssg_store_feed_post") === false) {
  /**
   * Speichert einen Feed-Beitrag per Prepared Statement, sofern er (anhand von
   * feeds_id + link) noch nicht existiert. Injection-sicher.
   */
  function rssg_store_feed_post(mysqli $link, int $feedsId, string $pubDate, string $linkUrl, string $title, string $description): void
  {
    $check = mysqli_prepare($link, 'SELECT id FROM `feeds_post` WHERE `feeds_id` = ? AND `link` = ? LIMIT 1');
    if ($check === false) {
      return;
    }
    mysqli_stmt_bind_param($check, 'is', $feedsId, $linkUrl);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    $exists = mysqli_stmt_num_rows($check) > 0;
    mysqli_stmt_close($check);
    if ($exists) {
      return;
    }

    $insert = mysqli_prepare($link, 'INSERT INTO `feeds_post` (`feeds_id`,`pubDate`,`link`,`title`,`description`) VALUES (?, ?, ?, ?, ?)');
    if ($insert === false) {
      return;
    }
    mysqli_stmt_bind_param($insert, 'issss', $feedsId, $pubDate, $linkUrl, $title, $description);
    mysqli_stmt_execute($insert);
    mysqli_stmt_close($insert);
  }
}
if (function_exists("addItem") === false) {
  /**
   * Speichert einen Feed-Beitrag (RSS 2.0 oder Atom) in der Tabelle feeds_post.
   *
   * Inhalte werden so gespeichert, wie SimpleXML sie liefert (UTF-8). Die früher
   * vorhandene Transkodierung nach ISO-8859-1 wurde entfernt, da sie in einer
   * utf8mb4-Datenbank Mojibake bei Umlauten (ä ö ü ß) erzeugt hat. Der Parameter
   * $iso_to_utf bleibt aus Kompatibilitätsgründen erhalten, wirkt aber nicht mehr
   * verlustbehaftet.
   *
   * @param int $iArt 1 = RSS 2.0, 2 = Atom
   */
  function addItem(string $iso_to_utf, ?SimpleXMLElement $v, int $id, bool|mysqli $link, int $iArt = 1): void
  {
    if (!$link instanceof mysqli) {
      return;
    }
    if ($v === null) {
      return;
    }
    switch ($iArt) {
      case 1:
        // RSS 2.0
        $vLink = (string)$v->link;
        $vTitle = (string)$v->title;
        $vDescription = (string)$v->description;
        $strtotime = strtotime((string)$v->pubDate);
        $vPubDate = date("Y-m-d H:i:s", $strtotime !== false ? $strtotime : time());
        rssg_store_feed_post($link, $id, $vPubDate, $vLink, $vTitle, $vDescription);
        break;
      case 2:
        // Atom
        $vTitle = (string)$v->title;
        $vContent = (string)$v->content;
        /** @var \SimpleXMLElement|null $linkElement */
        $linkElement = $v->link;
        $linkAttributes = $linkElement !== null ? $linkElement->attributes() : null;
        $url = '';
        if ($linkAttributes !== null) {
          foreach ($linkAttributes as $key => $value) {
            if ($key == 'href') {
              $url = (string)$value;
            }
          }
        }
        $strtotime = strtotime((string)$v->published);
        $vPubDate = date('Y-m-d H:i:s', $strtotime !== false ? $strtotime : time());
        rssg_store_feed_post($link, $id, $vPubDate, $url, $vTitle, $vContent);
        break;
    }
  }
}
