<?php
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
 * sichtbaren Copyright Hinweise mehr enthalten. Dadurch unterstützen Sie die Weiterentwicklung und würdigen diese Arbeit.
 */
if (!isset($db_host)) { $db_host = ''; }
if (!isset($db_passwort)) { $db_passwort = ''; }
if (!isset($db_user)) { $db_user = ''; }
if (!isset($db_datenbank)) { $db_datenbank = ''; }

/*
 * Seit PHP 8.1 wirft mysqli bei Fehlern standardmäßig Exceptions
 * (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT). Dieses Legacy-Skript prüft
 * Rückgabewerte selbst (=== false / or die). Damit DB-Fehler kontrolliert über
 * diese Prüfungen statt über ungefangene Exceptions (Fatal 500) laufen, wird
 * das Reporting hier bewusst abgeschaltet.
 */
mysqli_report(MYSQLI_REPORT_OFF);

$link = mysqli_connect($db_host, $db_user, $db_passwort, $db_datenbank);
if ($link === false) {
    // Details nur ins Log, dem Client keine Verbindungs-/Strukturinfos preisgeben.
    error_log('RSS Grabber: DB-Verbindung fehlgeschlagen: ' . mysqli_connect_error());
    if (headers_sent() === false) {
        http_response_code(500);
    }
    die('Die Datenbankverbindung ist derzeit nicht möglich. Bitte versuchen Sie es später erneut.');
}
/** @var mysqli $link */

/*
 * Durchgängiges UTF-8: ohne explizit gesetztes Verbindungs-Charset interpretiert
 * MySQL UTF-8-Bytes als latin1 und es entsteht Mojibake bei Umlauten (ä ö ü ß).
 */
mysqli_set_charset($link, 'utf8mb4');
