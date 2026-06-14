<?php
/** @var mysqli $link */
/** @var mixed $anzahl_grabber_pro_lauf */
/**
 * -----------------------------------------
 * RSS Grabber free v3.0 - 2026-06-14
 * -----------------------------------------
 * @copyright Copyright 2011, Schubertmedia/Nico Schubert
 * @link http://www.php-space.info/rss-grabber/ - Dokumentation und Informationen rund um das PHP Script.
 * @version free v3.0 (PHP 8.5)
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
require_once(__DIR__ . '/classes/FeedRepository.php');
require_once(__DIR__ . '/inc/auth.php');
rssg_require_login();
if (headers_sent() === false) {
    header('Content-Type: text/html; charset=UTF-8');
}
if (rssg_csrf_check($_POST['csrf'] ?? null) === false) {
    if (headers_sent() === false) {
        http_response_code(403);
    }
    echo 'Ungültiges Sicherheits-Token.';
    exit;
}
if(function_exists('simplexml_load_file')===false){
  echo 'Es steht in PHP die Funktion simplexml_load_file() nicht zur Verfügung.';
  exit;
}
$repo = new FeedRepository($link);
$now = time();
$ausgabe = '';
$anz_gesamt = $repo->countActive();
$anz_offen = $repo->countDue($now);
$dueFeeds = $repo->dueFeeds($now, max(1, (int)$anzahl_grabber_pro_lauf));

if ($dueFeeds !== []) {
  foreach ($dueFeeds as $feed) {
    $feedId = $feed['id'];
    // Feed mit Timeout laden, damit ein hängender Feed den Lauf nicht blockiert.
    // libxml-Parsefehler intern halten und Stream-Warnungen unterdrücken, damit
    // nicht erreichbare/ungültige Feeds keine PHP-Warnungen in die AJAX-Antwort
    // schreiben.
    libxml_use_internal_errors(true);
    $ctx = stream_context_create([
      'http'  => ['timeout' => 10, 'user_agent' => 'RSS-Grabber/2.0'],
      'https' => ['timeout' => 10],
    ]);
    $raw = @file_get_contents($feed['feed_url'], false, $ctx);
    $xml = ($raw !== false) ? @simplexml_load_string($raw, "SimpleXMLElement", LIBXML_NOCDATA) : false;
    libxml_clear_errors();

    if ($xml !== false && isset($iso_to_utf)) {
      foreach ($xml->channel->item as $v) {
        addItem((string)$iso_to_utf, $v, $feedId, $link);
      }
      foreach ($xml->entry as $v) {
        addItem((string)$iso_to_utf, $v, $feedId, $link, 2);
      }
    }
    $status = ($xml === false) ? 'fehler' : 'erfolgreich';
    $repo->markChecked($feedId, $now + 3600, $status);
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