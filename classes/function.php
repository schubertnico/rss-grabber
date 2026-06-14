<?php
/**
 * -----------------------------------------
 * RSS Grabber free v2.0 - 11.12.2022
 * -----------------------------------------
 * @copyright Copyright 2011, Schubertmedia/Nico Schubert
 * @link http://www.php-space.info/rss-grabber/ - Dokumentation und Informationen rund um das PHP Script.
 * @version free v2.0 (PHP8.1)
 * @abstract
 * Das Script darf kostenlos verwendet werden. Es müssen aber alle Copyright Hinweise erhalten bleiben.
 * Für einen einmaligen Betrag von 9,95 EUR erhalten Sie die Premium-Version. In der Premium-Version sind keine
 * sichtbaren Copyright Hinweise mehr enthalten. Daduch unterstutzen Sie die Weiterentwiklung und würdigen diese Arbeit.
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
        [$datum, $zeit] = explode(" ", $pubDate);
        [$jahr, $monat, $tag] = explode("-", $datum);
        [$stunde, $min, $sec] = explode(":", $zeit);
        $tage = ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"];
        $timestamp = mktime((int)$stunde, (int)$min, (int)$sec, (int)$monat, (int)$tag, (int)$jahr);
        return sprintf("%s den %02d.%02d.%04d um %02d:%02d:%02d Uhr", $tage[(int)date("w", (int)$timestamp)], $tag, $monat, $jahr, $stunde, $min, $sec);
    }
}
if (function_exists("addItem") === false) {
  /**
   * @param int $iArt
   */
  function addItem(string $iso_to_utf, ?SimpleXMLElement $v, int $id, bool|mysqli $link, $iArt = 1): void
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

        if ($iso_to_utf == 1) {
          $vLink = ((@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vLink) != false) ? (string)@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vLink) : $vLink);
          $vTitle = ((@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vTitle) != false) ? (string)@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vTitle) : $vTitle);
          $vDescription = ((@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vDescription) != false) ? (string)@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vDescription) : $vDescription);
        }
        $sql_select2 = "SELECT id FROM `feeds_post` WHERE `feeds_id` = '" . $id . "' AND `link`='" . mysqli_escape_string($link, $vLink) . "' LIMIT 1 ;";
        $query2 = mysqli_query($link, $sql_select2);
        if (!$query2 instanceof mysqli_result) {
          die((string)mysqli_errno($link));
        }
        if (mysqli_num_rows($query2) == 0) {
          $strtotime = strtotime($vPubDate);
          $vPubDate = date("Y-m-d H:i:s", $strtotime !== false ? $strtotime : time());
          $sql_insert = "INSERT INTO `feeds_post` (`feeds_id`,`pubDate`,`link`,`title`,`description`) VALUES ('" . $id . "',
        '" . mysqli_escape_string($link, $vPubDate) . "',
        '" . mysqli_escape_string($link, $vLink) . "',
        '" . mysqli_escape_string($link, $vTitle) . "',
        '" . mysqli_escape_string($link, $vDescription) . "');";
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

        if ($iso_to_utf == 1) {
          $vTitle = ((@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vTitle) != false) ? (string)@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vTitle) : $vTitle);
          $vDescription = ((@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vDescription) != false) ? (string)@iconv("UTF-8", "ISO-8859-1//TRANSLIT", $vDescription) : $vDescription);
        }
        $sql_select2 = "SELECT id FROM `feeds_post` WHERE `feeds_id` = '" . $id . "' AND `link`='" . mysqli_escape_string($link, $vLink) . "' LIMIT 1 ;";
        $query2 = mysqli_query($link, $sql_select2);
        if (!$query2 instanceof mysqli_result) {
          die((string)mysqli_errno($link));
        }
        if (mysqli_num_rows($query2) == 0) {
          /** @var \SimpleXMLElement|null $linkElement */
          $linkElement = $v->link;
          $linkAttributes = $linkElement !== null ? $linkElement->attributes() : null;
          $url = '';
          if ($linkAttributes !== null && count($linkAttributes) > 0) {
            foreach ($linkAttributes as $key => $value) {
              if ($key == 'href') {
                $valueStr = (string)$value;
                if ($iso_to_utf == 1) {
                  $iconvResult = @iconv("UTF-8", "ISO-8859-1//TRANSLIT", $valueStr);
                  $url = ($iconvResult !== false) ? $iconvResult : $valueStr;
                } else {
                  $url = $valueStr;
                }
              }
            }
          }

          $strtotime = strtotime($vPublished);
          $sql_insert = "INSERT INTO `feeds_post` (`feeds_id`,`pubDate`,`link`,`title`,`description`) VALUES ('" . $id . "',
        '" . mysqli_escape_string($link, date('Y-m-d H:i:s', $strtotime !== false ? $strtotime : time())) . "',
        '" . mysqli_escape_string($link, $url) . "',
        '" . mysqli_escape_string($link, $vTitle) . "',
        '" . mysqli_escape_string($link, $vContent) . "');";
          mysqli_query($link, $sql_insert) || die((string)mysqli_errno($link));
        }
        break;
    }
  }
}
