# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden hier dokumentiert.
Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/).

## [3.0.0] – 2026-06-14

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

### Behoben
- **Endless-Scroll lud das gesamte Layout** statt nur der Beiträge (AJAX-
  Erkennung bei `ajax=0`). Jetzt korrekt am Vorhandensein des Parameters erkannt.
- `limitch()` kürzt zeichenweise (`mb_substr`) – keine zerschnittenen Umlaute.
- Anzeigename bei `https`-Feeds (`rssg_feed_name()`).

### Entfernt
- `java/prototype.js`, `java/jQuery.js`, `java/jquery-1.4.2.min.js` (veraltet, 2010).

### Sicherheit
- Zugriffsschutz, CSRF, Output-Escaping und Prepared Statements schließen die
  zuvor offenen Lücken im Verwaltungsbereich.

## [2.0.0] – 2022-12-11

- Ursprüngliche Free-Version (PHP 8.1).
