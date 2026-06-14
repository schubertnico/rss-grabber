# Umsetzungsplan – Sicherheitshärtung

### 1. DB / Init
- `.docker/init.sql` + `install/index.php`: Tabelle `admin` (utf8mb4) anlegen,
  Default `admin`/`admin` (bcrypt) seeden.
- Live-Dev-DB: `admin`-Tabelle anlegen + seeden.

### 2. Helper
- `inc/auth.php`: `session_start`, `rssg_csrf_token()`, `rssg_csrf_check()`,
  `rssg_is_logged_in()`, `rssg_require_login()`.
- `classes/function.php`: `rssg_e()` (htmlspecialchars-Wrapper),
  `rssg_safe_url()` (Schema-Whitelist).

### 3. Login/Logout
- `login.php`: Formular (Template `tpl/login_form.html`), Verifikation per
  Prepared Statement gegen `admin`, Session setzen, Redirect.
- `logout.php`: Session beenden, Redirect auf `login.php`.
- `tpl/navigation.html`: Logout-Link.

### 4. Controller absichern
- Geschützte Controller: `require_once inc/auth.php; rssg_require_login();`.
- Schreibaktionen: `rssg_csrf_check()` vor DB-Schreibzugriff.
- Prepared Statements in add/edit/delete + `addItem()` + Sync-Update.
- XSS-Escaping in `ausgabe.php` und `feeds_verwalten.php`.
- IN-Listen in `ausgabe.php` per `intval` absichern.

### 5. Templates
- `feed_hinzufuegen_form.html`, `feed_bearbeiten_form.html`: `{CSRF}`-Hidden-Feld.

### 6. Tests
- `tests/Unit/AuthTest.php`: CSRF-Token/Check, `rssg_e`, `rssg_safe_url`,
  password_hash/verify-Roundtrip.
- `tests/Integration/AddItemTest.php`: weiterhin grün (Prepared-Statement-Variante).
- `tests/Integration/ControllerSmokeTest.php`: Session/Login für geschützte
  Seiten setzen; `login.php` ergänzen.
- `tests/e2e/`: `beforeEach`-Login; neue Tests für Redirect-ohne-Login,
  CSRF-Abweisung, XSS-Escaping.

### 7. Verifikation & Abschluss
- PHPUnit + Playwright im Docker grün, `php-error.log` leer.
- README/CHANGELOG aktualisieren.
- Commit → Merge nach `main` → Push → Feature-Branch löschen.

## Risiken
- **Bestehende Tests/E2E** greifen jetzt auf geschützte Seiten zu → müssen sich
  anmelden. Wird in Smoke- und E2E-Tests berücksichtigt.
- **Prepared Statements in `addItem()`** ändern den Query-Aufbau → durch
  bestehenden UTF-8-Roundtrip-Test abgesichert.
