# Changelog

## [Unveröffentlicht] – Repository-Schicht & Scroll-Bugfix

### Behoben
- **Endless-Scroll lud das gesamte Layout statt nur der Beiträge.** Ursache: Die
  AJAX-Erkennung in `ausgabe.php` prüfte `(int)$ajax !== 0`, womit der erste
  Nachlade-Schritt (`ajax=0`) die Vollseite zurückgab. Jetzt wird am
  **Vorhandensein** des `ajax`-Parameters erkannt. Per E2E-Test abgesichert.

### Geändert – Architektur
- DB-/Geschäftslogik aus den Controllern in eine testbare Repository-Schicht
  ausgelagert: `classes/FeedRepository.php` (alle Feed-/Post-/Sync-Queries) und
  `classes/AdminRepository.php` (Login-Verifikation). Controller sind nun dünn
  (Request → Repository → Render). Kein inline-SQL mehr in den Controllern.
- Hilfsfunktion `rssg_feed_name()` (Host-Name aus URL; behebt nebenbei die
  fehlerhafte Anzeige bei `https`-Feeds).

### Tests
- `FeedRepositoryTest` + `AdminRepositoryTest` (Integration gegen Test-DB) decken
  die Repository-Schicht direkt ab; Unit-Test für `rssg_feed_name()`.
- E2E-Test gegen die Scroll-Regression (Fragment statt Layout).

## [Unveröffentlicht] – Statisches Analyse-Gate (PHPStan)

### Hinzugefügt
- **PHPStan Level 8** (`phpstan.neon`) + `phpstan-phpunit`-Extension als
  Dev-Abhängigkeit; Composer-Shortcut `composer analyse`. Lauf ist fehlerfrei.

### Behoben (durch die Analyse aufgedeckt)
- `inc/config.php`: redundante `@var string`-Tags entfernt.
- `login.php`: fehlende `@var mysqli $link`-Annotation ergänzt.
- `logout.php`: `setcookie((string)session_name(), …)`.
- `classes/function.php`: `rssg_render_feed_post()`-PHPDoc auf `array-key`.

## [Unveröffentlicht] – Frontend-Modernisierung + CSRF-Sync

### Geändert
- **Kein prototype.js / jQuery 1.4.2 mehr:** Die gesamte Frontend-Logik
  (Feed-Synchronisierung + Endless-Scroll) läuft jetzt über abhängigkeitsfreies
  Vanilla-JS (`java/rss-grabber.js`, `fetch`). `tpl/layout.html` lädt nur noch
  diese eine Datei.
- **CSRF-Schutz für die Synchronisierung:** `graber_ajax.php` verlangt jetzt ein
  CSRF-Token (`POST csrf`) und weist Anfragen ohne gültiges Token mit HTTP 403
  ab. Das Token wird per `data-csrf`-Attribut an das JS übergeben.

### Entfernt
- `java/prototype.js`, `java/jQuery.js`, `java/jquery-1.4.2.min.js` (veraltet, 2010).

### Tests
- E2E um Sync-Flow (Vanilla-JS) und CSRF-Negativtest erweitert.

## [Unveröffentlicht] – Robustheit & Code-Qualität (Runde 2)

### Geändert
- **Sync-Robustheit (`graber_ajax.php`):** Feed-Abruf mit 10-s-Timeout
  (Stream-Context), kein `die()` mehr – ein einzelner defekter Feed bricht den
  Lauf nicht ab.
- **Installer (`install/index.php`):** Die generierte `config.php` wird per
  `var_export` erzeugt (Werte mit `'`/`"`/`\` können die Datei nicht mehr
  brechen), moderner Schutz-Guard, POST-Werte `is_string`-geprüft.
- **Kein Info-Leak (`db.php`):** Verbindungsfehler → generische Meldung +
  `error_log` + HTTP 500.
- **`limitch()`** kürzt zeichenweise (`mb_substr`), zerschneidet keine Umlaute.
- **`ausgabe.php`:** doppelte Render-Logik in `rssg_render_feed_post()`
  ausgelagert (zentrales Escaping). Damit wird auch der zuvor **ungeescapte
  AJAX-Zweig** abgesichert (verbleibende XSS-Lücke im nachgeladenen Inhalt).
  Feed-ID-Listen als Integer (`intval`), keine `IN ('')`-Query.

### Tests
- Unit-Tests für `rssg_render_feed_post()` und multibyte-`limitch()`.

## [Unveröffentlicht] – Sicherheitshärtung (Auth, CSRF, XSS, SQLi)

### Hinzugefügt
- **Zugriffsschutz:** Session-basiertes Login (`login.php`/`logout.php`) mit
  Admin-Tabelle in der DB (bcrypt). Geschützt: Feeds verwalten/anlegen/bearbeiten,
  Synchronisierung. Öffentlich bleibt nur `ausgabe.php`/`premium-version.php`.
  Default-Zugang **admin / admin** (per Init-Skript, **bitte sofort ändern**).
- **CSRF-Schutz:** Pro-Session-Token (`random_bytes`, `hash_equals`) für alle
  schreibenden Aktionen (anlegen, bearbeiten, löschen).
- **DB:** Neue Tabelle `admin` in `.docker/init.sql` und `install/index.php`.
- `inc/auth.php` (Session/CSRF/Login-Helfer), `rssg_e()`/`rssg_safe_url()`.

### Geändert
- **XSS:** Alle DB-Inhalte werden beim Output mit `htmlspecialchars` kodiert
  (`ausgabe.php`, `feeds_verwalten.php`); `href` nur mit http(s)-Schema.
- **SQL-Injection:** Login, Feed anlegen/bearbeiten/löschen, `addItem()` und das
  Sync-Update nutzen jetzt **Prepared Statements**; ID-Listen per `intval`.

### Tests
- `tests/Unit/AuthTest.php` (CSRF, Escaping, Passwort-Hash).
- Controller-Smoke-Tests mit angemeldeter Session; E2E um Login-,
  CSRF-/XSS- und Logout-Tests erweitert.

## [Unveröffentlicht] – PHP 8.5 & UTF-8 Modernisierung

### Geändert – UTF-8 / Umlaute
- `db.php`: Verbindung setzt jetzt explizit `mysqli_set_charset($link, 'utf8mb4')`.
- `classes/function.php` (`addItem`): Die verlustbehaftete Transkodierung
  `UTF-8 → ISO-8859-1//TRANSLIT` wurde entfernt. Feed-Inhalte werden als UTF-8
  gespeichert. Das war die Hauptursache für defekte Umlaute (Mojibake).
- `init.sql`, `install/index.php`: Tabellen `feeds`/`feeds_post` werden als
  `utf8mb4` (Collation `utf8mb4_unicode_ci`) angelegt.
- `graber_ajax.php`: sendet `Content-Type: text/html; charset=UTF-8`.

### Geändert – PHP 8.5
- `mysqli_escape_string()` → `mysqli_real_escape_string()` (8.5-Deprecation)
  in `feed_hinzufuegen.php`, `feed_bearbeiten.php`, `feeds_verwalten.php`,
  `classes/function.php`.
- `classes/parase.php`: `fopen`/`fread`/`filesize` → `file_get_contents`
  (behebt fatalen `ValueError` bei leerer Template-Datei); explizite
  Methoden-Sichtbarkeit; fehlende Datei wirft jetzt `RuntimeException`.
- `db.php` / `install/index.php`: `mysqli_report(MYSQLI_REPORT_OFF)` – das
  Legacy-Rückgabewert-Pattern bleibt gültig, DB-Fehler führen nicht mehr zu
  ungefangenen Exceptions (Fatal 500).
- `index.php` und alle Controller: `exit;` nach `header('Location: …')`,
  `require_once` statt `include`, kein `@` mehr vor `file_exists`.
- `ausgabe.php`: Schutz gegen `DivisionByZeroError` bei der Paginierung;
  `ajax`-Parameter typsicher als `int`; abgesicherte Array-Zugriffe.
- `classes/function.php` (`date_mysql2german`): Eingabeformat wird validiert
  (keine „Undefined array key“-Warnungen mehr bei ungültigen Datumswerten).
- `graber_ajax.php`: `count()` auf SimpleXML durch direkte Iteration ersetzt;
  `LIMIT` typsicher als `int`; libxml-Fehler werden intern gehalten.
- `inc/config.php`: DB-Parameter über Umgebungsvariablen (`RSSG_DB_*`)
  überschreibbar; numerische Einstellungen als `int`.

### Hinzugefügt – Tests & Tooling
- `composer.json` + PHPUnit 11.5 (Unit-, Integrations- und Controller-Smoke-Tests).
- PHPUnit-Coverage ≥ 80 % über `classes/` (aktuell ~94 %).
- Playwright-E2E-Suite (`tests/e2e/`) gegen die Docker-Instanz.
- `.docker/`: PHP-8.5-Image inkl. Composer + pcov (Coverage).
- Dokumentation: `docs/SPECS.md`, `docs/PLAN.md`, `README.md`.

### Nicht enthalten (bewusst, außerhalb des Migrationsumfangs)
- Umstellung auf Prepared Statements, CSRF-Schutz und Authentifizierung sind als
  Folgearbeiten dokumentiert (siehe Audit), aber nicht Teil dieser Migration,
  um das Verhalten der Free-Version stabil zu halten.
