<?php
if (!isset($db_host)) { $db_host = ''; }
if (!isset($db_passwort)) { $db_passwort = ''; }
if (!isset($db_user)) { $db_user = ''; }
if (!isset($db_datenbank)) { $db_datenbank = ''; }
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
$link = mysqli_connect($db_host, $db_user, $db_passwort);
if ($link === false) {
    die('Not connected : ' . mysqli_connect_error());
}
/** @var mysqli $link */

// make foo the current db
$db_selected = mysqli_select_db($link, $db_datenbank);
if (!$db_selected) {
    die ('Can\'t use foo : ' . mysqli_error($link));
}