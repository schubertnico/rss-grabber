<?php
/**
 * RSS Grabber Config (Docker-Entwicklungsumgebung)
 *
 * Diese Datei wird normalerweise vom Install-Skript erzeugt. Für die lokale
 * Docker-Umgebung sind die Werte passend zum db-Service aus
 * .docker/docker-compose.yml gesetzt. Über Umgebungsvariablen (RSSG_DB_*)
 * lassen sich die DB-Parameter überschreiben (z. B. für die Test-Datenbank),
 * ohne die Produktiv-Installation zu beeinflussen.
 */
if (realpath($_SERVER["SCRIPT_FILENAME"] ?? '') === __FILE__) {
    header('Location: ../index.php');
    die();
}
$script_version = '3.0';
$db_host = getenv('RSSG_DB_HOST') ?: 'db';
$db_datenbank = getenv('RSSG_DB_NAME') ?: 'rss_grabber';
$db_user = getenv('RSSG_DB_USER') ?: 'rss_grabber';
$db_passwort = getenv('RSSG_DB_PASS') ?: 'rss_grabber_secret';
/** @var int */
$anzahl_grabber_pro_lauf = 10;
/** @var int */
$anz_anzeige = 25;
/** @var int */
$max_laege_description = 250;
/** @var int */
$iso_to_utf = 1;
