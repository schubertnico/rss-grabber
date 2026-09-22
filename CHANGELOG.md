# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden hier dokumentiert.
Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/).

## [3.0.0] – 2026-09-22

Große Modernisierung der Free-Version: lauffähig unter **PHP 8.5**, durchgängiges
**UTF-8**, abgesicherter Verwaltungsbereich, abhängigkeitsfreies Frontend und eine
testbare Architektur mit automatisierten Tests (PHPUnit + Playwright) und
statischer Analyse (PHPStan Level 8).

### Highlights
- **PHP 8.5** statt 8.1; keine Deprecations/Warnungen mehr.
- **UTF-8 ohne Mojibake** (utf8mb4, korrekte Umlaute ä ö ü ß).
- **Login + CSRF + XSS-Schutz + Prepared Statements** für den Admin-Bereich.
- **Kein prototype.js/jQuery** mehr – schlankes Vanilla-JS.
- **Repository-Architektur**, ~90 % Test-Coverage der Kernlogik.

### Hinzugefügt
- Session-basiertes **Login** (`login.php`/`logout.php`) mit `admin`-Tabelle
  (bcrypt). Default-Zugang **admin / admin** (nach Installation ändern).
- **CSRF-Schutz** (Pro-Session-Token) für alle Schreibaktionen und den Sync.
- Repository-Schicht `FeedRepository` / `AdminRepository`.
- Abhängigkeitsfreies Frontend `java/rss-grabber.js` (Sync + Endless-Scroll).
- Tests: PHPUnit (Unit/Integration/Smoke) + Playwright-E2E.
- Statische Analyse **PHPStan Level 8** (`composer analyse`).
- Docker-Entwicklungsumgebung unter `.docker/` (PHP 8.5, Composer, pcov).
- **Bebilderte Installationsanleitung** (`Installationsanleitung_3.0.pdf`) mit
  Abschnitten zum Umstieg von Version 2.0, zur Bedienung und zur Fehlersuche.
- Demonstrations-Screenshots unter `Screenshots/v3.0/`, erzeugt über
  `build/screenshots.mjs`.

### Geändert
- **UTF-8:** `mysqli_set_charset('utf8mb4')`, verlustbehaftete
  iconv-Transkodierung in `addItem()` entfernt, Tabellen als `utf8mb4`.
- **PHP 8.5:** `mysqli_real_escape_string`, `file_get_contents` im
  Template-Parser, `exit` nach Redirects, `require_once`, DivisionByZero-Schutz,
  robustes `date_mysql2german`, `mysqli_report(OFF)`.
- **Sicherheit:** alle DB-Ausgaben werden escaped (`htmlspecialchars`, http(s)-
  Whitelist); SQL über Prepared Statements.
- **Robustheit:** Feed-Sync mit Timeout und Best-Effort (kein `die()`); Installer
  erzeugt `config.php` injektionssicher per `var_export`; `db.php` ohne
  Info-Leak.
- **Architektur:** DB-/Geschäftslogik aus den Controllern in Repositories
  ausgelagert; Controller sind nun dünn.
- **Mindestanforderung** im Installer auf PHP **8.5** angehoben.
- Versionskennzeichnung durchgängig auf **free v3.0**.
- Beim Feed-Abruf entfällt der Kontextabschnitt `https`. PHP wertet ihn nicht
  aus; für beide Schemata gilt der Abschnitt `http`, der den Zeitablauf schon
  gesetzt hat. Der Eintrag war also wirkungslos, kein Fehlverhalten.

### Behoben
- **Endless-Scroll lud das gesamte Layout** statt nur der Beiträge (AJAX-
  Erkennung bei `ajax=0`). Jetzt korrekt am Vorhandensein des Parameters erkannt.
- `limitch()` kürzt zeichenweise (`mb_substr`) – keine zerschnittenen Umlaute.
- Anzeigename bei `https`-Feeds (`rssg_feed_name()`).
- **Die mitgelieferten Beispiel-Feeds ließen sich nicht abrufen.** Der beim
  Feed-Abruf gesendete Anwendungsname enthielt das Wort „Grabber", und
  verbreitete Schutzregeln auf Webservern (ModSecurity und Verwandte) weisen
  solche Anfragen mit HTTP 403 ab – auch php-space.info selbst. Nach der
  Installation stand deshalb bei allen drei Beispiel-Feeds „fehler". Der
  Name lautet jetzt `PHP-Space RSS-Reader/3.0` und nennt die Projektadresse,
  damit Betreiber die Zugriffe weiterhin zuordnen können. Der Fehler bestand
  schon in der Version 2.0 (`RSS-Grabber/2.0`).
- **Kein Weg zur Anmeldung.** Die Navigation zeigte immer „Logout", auch
  abgemeldet. Wer die öffentliche Beitragsanzeige aufrief, fand keinen Weg in
  den Verwaltungsbereich und musste `login.php` erraten. Der Punkt heißt
  jetzt „Anmelden", solange niemand angemeldet ist. Die beiden öffentlichen
  Seiten fragen den Status ab, ohne jedem Besucher eine Sitzung anzulegen
  (`RSSG_SESSION_NUR_MIT_COOKIE`): Wer kein Sitzungscookie mitbringt, kann
  nicht angemeldet sein.
- **PHP-Warnung beim Synchronisieren von Atom-Feeds.** Der Abruf ging für
  jeden Feed beide Pfade durch, `$xml->channel->item` und `$xml->entry`. Ein
  Atom-Feed hat kein `<channel>`, der Zugriff ergab `null`, und PHP 8 warnt bei
  `foreach` über `null`. Die neue Funktion `rssg_feed_eintraege()` erkennt das
  Format am Wurzelelement; fünf Unit-Tests decken RSS, Atom, leeren Feed und
  unbekanntes Format ab. Aufgefallen im Einrichtungsvideo – die Tests liefen
  bis dahin nur mit RSS-Feeds.
- **Die mitgelieferte `.htaccess` schaltete die Fehleranzeige ein**
  (`display_errors on`) und lenkte das Protokoll nach
  `/var/log/php_errors.log` – Einstellungen aus der Entwicklung, seit dem
  ersten Commit im Paket. Jede Warnung stand damit für Besucher sichtbar samt
  Serverpfad im Browser. Auf Hostern mit PHP-FPM oder FastCGI führen
  `php_flag`-Zeilen außerhalb eines `IfModule`-Blocks zudem zu einem
  Serverfehler 500. Die Datei schaltet die Anzeige jetzt ab, nur unter
  `mod_php`.
- **`inc/` ist vor direktem Abruf geschützt** (eigene `.htaccess`). Die
  Anleitung sagte das bereits zu, es traf aber nicht zu.
- **Die Anleitung empfahl einen Cronjob für die Synchronisierung.** Das geht
  seit Version 3.0 nicht mehr: Die Synchronisierung verlangt eine Anmeldung,
  ein zeitgesteuerter Abruf landet am Anmeldeformular. Automatisches
  Synchronisieren ist Teil der Premium-Version.
- **Die Premium-Seite nannte nur den Preis**, nicht den Inhalt. Sie führt jetzt
  die beiden Unterschiede auf: Cronjob-Unterstützung und kein
  Copyright-Hinweis.
- **Überholter Hinweis nach der Installation.** Die Erfolgsmeldung riet zu
  einem Passwortschutz für das Verzeichnis, „da ansonsten jeder Zugriff auf
  Ihre Feeds hat" – ein Satz aus der Zeit vor dem Login. Sie verweist jetzt
  auf die Anmeldung und auf die Pflicht, das Standardpasswort zu ändern.

### Entfernt
- `java/prototype.js`, `java/jQuery.js`, `java/jquery-1.4.2.min.js` (veraltet, 2010).

### Sicherheit
- Zugriffsschutz, CSRF, Output-Escaping und Prepared Statements schließen die
  zuvor offenen Lücken im Verwaltungsbereich.

## [2.0.0] – 2022-12-11

- Ursprüngliche Free-Version (PHP 8.1).
