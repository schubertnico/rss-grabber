<?php
/** @var mysqli $link */
/** @var mixed $anzahl_grabber_pro_lauf */
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
if (file_exists(__DIR__ . '/inc/config.php') === false) {
    exit;
}
require_once(__DIR__ . '/inc/config.php');
require_once(__DIR__ . '/db.php');
require_once(__DIR__ . '/classes/function.php');
if (headers_sent() === false) {
    header('Content-Type: text/html; charset=UTF-8');
}
if(function_exists('simplexml_load_file')===false){
  echo 'Es steht in PHP die Funktion simplexml_load_file() nicht zur Verfügung.';
  exit;
}
$ausgabe = '';
$sql_select = "SELECT id FROM `feeds` WHERE `check` = '1';";
$query = mysqli_query($link, $sql_select);
$anz_gesamt = ($query instanceof mysqli_result) ? (int)mysqli_num_rows($query) : 0;

$sql_select = "SELECT id FROM `feeds` WHERE `check` = '1' AND `last_check`<'" . time() . "';";
$query = mysqli_query($link, $sql_select);
$anz_offen = ($query instanceof mysqli_result) ? (int)mysqli_num_rows($query) : 0;

$sql_select = "SELECT id, feed_url FROM `feeds` WHERE `check` = '1' AND `last_check`<'" . time() . "' LIMIT " . max(1, (int)$anzahl_grabber_pro_lauf) . ";";
$query = mysqli_query($link, $sql_select) or die(mysqli_errno($link));
if (!$query instanceof mysqli_result) {
    die('Query failed');
}

if (mysqli_num_rows($query) != 0) {
  while ($daten = mysqli_fetch_assoc($query)) {
    // libxml-Parsefehler intern halten und Stream-Warnungen unterdrücken,
    // damit nicht erreichbare/ungültige Feeds keine PHP-Warnungen in die
    // AJAX-Antwort schreiben.
    libxml_use_internal_errors(true);
    $xml = @simplexml_load_file((string)$daten["feed_url"], "SimpleXMLElement", LIBXML_NOCDATA);
    libxml_clear_errors();

    if ($xml !== false && isset($iso_to_utf)) {
      foreach ($xml->channel->item as $v) {
        addItem((string)$iso_to_utf, $v, (int)$daten["id"], $link);
      }
      foreach ($xml->entry as $v) {
        addItem((string)$iso_to_utf, $v, (int)$daten["id"], $link, 2);
      }
    }
    $status = (($xml === false) ? 'fehler' : 'erfolgreich');
    $sql_update = "UPDATE `feeds` SET `last_check` = '" . (time() + 3600) . "', `last_status`='" . $status . "' WHERE `id` = '" . $daten["id"] . "' LIMIT 1;";
    mysqli_query($link, $sql_update) || die(mysqli_errno($link));
  }
  if (($anz_gesamt - $anz_offen) == 0) {
      $ausgabe .= '<img src="img/ajax-loader.gif" alt=""> Die Synchronisierung beginnt, bitte warten...';
  } else {
      $ausgabe .= '<img src="img/ajax-loader.gif" alt=""> Es wurden ' . ($anz_gesamt - $anz_offen) . ' Feed(s) von ' . $anz_gesamt . ' Feed(s) wurden synchronisiert.';
  }
} else {
  $ausgabe = 'Fertig, es wurden alle ' . $anz_gesamt . ' Feed(s) synchronisiert.';
}
echo $ausgabe;