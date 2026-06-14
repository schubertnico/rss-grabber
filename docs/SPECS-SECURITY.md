# Spezifikation – Sicherheitshärtung (Auth, CSRF, XSS, SQLi)

## Ziel

Der Admin-Bereich des RSS Grabbers wird abgesichert. Drei reale Mängel werden
behoben, ohne den öffentlichen Anzeigebereich (`ausgabe.php`) einzuschränken.

## Anforderungen

### A. Zugriffsschutz (Authentifizierung)

1. Session-basiertes Login. Anmeldedaten liegen in der DB-Tabelle `admin`
   (Passwort als bcrypt-Hash, `password_hash`/`password_verify`).
2. **Geschützt** (Login erforderlich): `feeds_verwalten.php`,
   `feed_hinzufuegen.php`, `feed_bearbeiten.php`, `feeds_synchronisieren.php`,
   `graber_ajax.php`.
3. **Öffentlich** (kein Login): `index.php`, `ausgabe.php`, `premium-version.php`,
   `login.php`.
4. Nicht angemeldete Zugriffe auf geschützte Seiten werden auf `login.php`
   weitergeleitet (HTTP-Redirect, danach `exit`).
5. `logout.php` beendet die Session.
6. Default-Zugang `admin` / `admin` wird per Init-Skript angelegt und MUSS nach
   der Installation geändert werden (dokumentiert).

### B. CSRF-Schutz

1. Pro Session ein zufälliges Token (`random_bytes`, `hash_equals`-Vergleich).
2. Schreibende Formularaktionen (Feed anlegen, bearbeiten) führen ein
   verstecktes `csrf`-Feld mit; das Löschen führt das Token im Link mit.
3. Fehlt/stimmt das Token nicht, wird die Aktion abgewiesen (kein DB-Schreibzugriff).

### C. XSS-Schutz (Output-Escaping)

1. Alle aus der DB gelesenen Werte werden beim HTML-Output mit
   `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` kodiert
   (`ausgabe.php`: Titel/Link/Beschreibung/Feed-Name; `feeds_verwalten.php`:
   URLs/Status).
2. Für `href`-Attribute wird zusätzlich das URL-Schema auf `http(s)` geprüft
   (sonst `#`).

### D. SQL-Injection (Prepared Statements)

1. Alle Queries mit Benutzer-/Feed-Eingaben nutzen `mysqli_prepare` +
   `bind_param`: Login, Feed anlegen/bearbeiten/löschen, `addItem()`,
   `last_check`-Update beim Sync.
2. Aus der DB stammende ID-Listen (`IN (...)` in `ausgabe.php`) werden per
   `intval` abgesichert; leere Liste überspringt die Query.

## DB-Änderung (Init-Skript)

Neue Tabelle `admin` (utf8mb4) wird in `.docker/init.sql` und
`install/index.php` angelegt und mit dem Default-Admin befüllt.

## Akzeptanzkriterien

- [ ] Geschützte Seiten ohne Login → Redirect auf `login.php`.
- [ ] Login mit `admin`/`admin` → Zugriff; Logout beendet Session.
- [ ] POST ohne gültiges CSRF-Token → keine Änderung.
- [ ] Feed mit `<script>`/HTML im Titel wird escaped ausgegeben (kein aktives JS).
- [ ] Eingaben mit `'`/`"` brechen keine Query (Prepared Statements).
- [ ] PHPUnit grün (inkl. neuer Auth-/Security-Tests), Coverage `classes/` ≥ 80 %.
- [ ] Playwright-E2E grün (Login-Flow, CSRF-Abweisung, XSS-Escaping).
- [ ] `php-error.log` bleibt leer.

## Nicht-Ziele (dokumentierte Folgearbeiten)

- CSRF für den AJAX-Sync-Aufruf (ist login-geschützt; Token-Übergabe über die
  Legacy-prototype.js-Kette wird separat behandelt).
- Mehrbenutzer-/Rollenverwaltung, Passwort-Ändern-UI, Rate-Limiting.
