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
if (function_exists("limitch") === false) {
    function limitch(?string $value, int $lenght): string
    {
        $value = $value ?? '';
        if (strlen($value) >= $lenght) {
            $limited = "";
            $limited .= substr($value, 0, $lenght);
            $limited .= "...";
            return stripslashes($limited);
        }
        return stripslashes($value);
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
        $vPubDate = (string)$v->pubDate;

        $sql_select2 = "SELECT id FROM `feeds_post` WHERE `feeds_id` = '" . $id . "' AND `link`='" . mysqli_real_escape_string($link, $vLink) . "' LIMIT 1 ;";
        $query2 = mysqli_query($link, $sql_select2);
        if (!$query2 instanceof mysqli_result) {
          die((string)mysqli_errno($link));
        }
        if (mysqli_num_rows($query2) == 0) {
          $strtotime = strtotime($vPubDate);
          $vPubDate = date("Y-m-d H:i:s", $strtotime !== false ? $strtotime : time());
          $sql_insert = "INSERT INTO `feeds_post` (`feeds_id`,`pubDate`,`link`,`title`,`description`) VALUES ('" . $id . "',
        '" . mysqli_real_escape_string($link, $vPubDate) . "',
        '" . mysqli_real_escape_string($link, $vLink) . "',
        '" . mysqli_real_escape_string($link, $vTitle) . "',
        '" . mysqli_real_escape_string($link, $vDescription) . "');";
          mysqli_query($link, $sql_insert) || die((string)mysqli_errno($link));
        }
        break;
      case 2:
        // Atom
        $vTitle = (string)$v->title;
        $vDescription = (string)$v->description;
        $vLink = (string)$v->link;
        $vPublished = (string)$v->published;
        $vContent = (string)$v->content;

        $sql_select2 = "SELECT id FROM `feeds_post` WHERE `feeds_id` = '" . $id . "' AND `link`='" . mysqli_real_escape_string($link, $vLink) . "' LIMIT 1 ;";
        $query2 = mysqli_query($link, $sql_select2);
        if (!$query2 instanceof mysqli_result) {
          die((string)mysqli_errno($link));
        }
        if (mysqli_num_rows($query2) == 0) {
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

          $strtotime = strtotime($vPublished);
          $sql_insert = "INSERT INTO `feeds_post` (`feeds_id`,`pubDate`,`link`,`title`,`description`) VALUES ('" . $id . "',
        '" . mysqli_real_escape_string($link, date('Y-m-d H:i:s', $strtotime !== false ? $strtotime : time())) . "',
        '" . mysqli_real_escape_string($link, $url) . "',
        '" . mysqli_real_escape_string($link, $vTitle) . "',
        '" . mysqli_real_escape_string($link, $vContent) . "');";
          mysqli_query($link, $sql_insert) || die((string)mysqli_errno($link));
        }
        break;
    }
  }
}
