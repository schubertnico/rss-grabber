# Umsetzungsplan – PHP 8.5 + UTF-8 + Tests

## Reihenfolge (TDD-orientiert)

### 1. Testinfrastruktur
- `composer.json` mit `phpunit/phpunit` (dev) + Plattform-Requirements.
- `phpunit.xml`: Testsuites `unit`/`integration`, Coverage-Scope `classes/`,
  `failOnWarning`/`failOnNotice` aktiv.
- `tests/bootstrap.php`: Error-Handler, der jede PHP-Diagnose (Warning/Notice/
  Deprecation) in eine Exception wandelt → Tests scheitern bei PHP-Fehlern.
- pcov + Composer im Web-Container (Dockerfile-Ergänzung).

### 2. Unit-Tests schreiben (rot)
- `tests/Unit/FunctionsTest.php`
  - `limitch()`: Kürzung, „...“-Anhang, `stripslashes`, Null-Eingabe.
  - `date_mysql2german()`: korrekte deutsche Formatierung, Leer-/Default-Wert.
- `tests/Unit/ParseTest.php`
  - `PARSE::TEMPLATE()` ersetzt `{PLATZHALTER}`, fehlende Keys → leer.
  - `TEMPLATE_RETURN()` / `TEMPLATE_AUSGABE()` (Output-Buffering).
- `tests/Integration/AddItemTest.php` (Test-DB)
  - RSS-2.0- und Atom-Eintrag wird gespeichert.
  - **Umlaut-Test:** Titel `Schöne Grüße äöüß` bleibt nach Speichern/Lesen
    unverändert (kein Mojibake). → schlägt zunächst fehl (iconv-Bug).
  - Duplikat wird nicht doppelt gespeichert.

### 3. Quellcode anpassen (grün)
- `inc/config.php`: DB-Parameter aus Umgebungsvariablen (`RSSG_DB_*`) mit
  Docker-Defaults; `$anz_anzeige`/`$max_laege_description` als `int`.
- `db.php`: `mysqli_set_charset($link, 'utf8mb4')`.
- `classes/function.php` → `addItem()`: iconv-Transkodierung entfernen
  (UTF-8 unverändert speichern). `$iso_to_utf` bleibt als Parameter erhalten
  (Abwärtskompatibilität), wirkt aber nicht mehr verlustbehaftet.
- `feeds_synchronisieren.php`: `LIMIT (int)$anzahl_grabber_pro_lauf`.
- `init.sql` + `install/index.php`: Tabellen als `utf8mb4`.
- Weitere vom Audit-Workflow gemeldete 8.5-Punkte.

### 4. Controller-Smoke-Tests
- `tests/Integration/ControllerSmokeTest.php`: jede Seite in isoliertem Prozess
  einbinden, Ausgabe darf keine Fehler-Schlüsselwörter enthalten.

### 5. Tests grün + Coverage ≥ 80 %
- `vendor/bin/phpunit --coverage-text` im Web-Container.

### 6. Playwright-E2E
- `tests/e2e/` (Playwright, Node) gegen `http://rss-grabber_web` bzw.
  `localhost:8340`.
- Prüft: alle Seiten 200, kein PHP-Fehler im HTML, CRUD-Fluss, Umlaute.
- Konsolen-/HTTP-Fehler (4xx/5xx) lassen den Test scheitern.

### 7. Docker-Verifikation
- Stack neu bauen, alle Seiten aufrufen, `php-error.log` muss leer bleiben.

### 8. Doku + Commit + Merge
- README/CHANGELOG aktualisieren.
- Commit auf Feature-Branch, Merge nach `main`, Feature-Branch löschen.

## Risiken & Gegenmaßnahmen
- **mysqli wirft Exceptions (8.1+ Default):** Smoke-Tests prüfen reale Pfade.
- **Coverage mit Prozessisolation:** Coverage-Scope bewusst auf `classes/`
  begrenzt; Controller über Smoke/E2E.
- **Bestehende latin1-Daten:** Migration ist verhaltensändernd – neue Inhalte
  sind sauberes UTF-8; Alt-Daten werden nicht rückwirkend konvertiert (Free-
  Version ohne Bestandsdaten in der Docker-Umgebung).
