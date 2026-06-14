# Spezifikation – Repository-Schicht (Architektur & Testbarkeit)

## Ziel

Die in den Controllern verstreute DB-/Geschäftslogik wird in eine schmale,
unit-/integrationstestbare Repository-Schicht ausgelagert. Die Controller werden
dünn (Request parsen → Repository aufrufen → rendern). Verhalten bleibt gleich.

## Anforderungen

### A. Repositories (`classes/`)
1. `FeedRepository` (Konstruktor: `mysqli $link`) kapselt alle Feed-/Post-Queries:
   `all()`, `feedMap()`, `find()`, `existsByFeedUrl()`, `add()`, `update()`,
   `delete()`, `latestPosts()`, `countPosts()`, `countActive()`, `countDue()`,
   `dueFeeds()`, `markChecked()`. Eingaben über Prepared Statements; aus der DB
   stammende ID-Listen werden per `intval` abgesichert.
2. `AdminRepository` (Konstruktor: `mysqli $link`) kapselt `verifyLogin()`
   (Prepared Statement + `password_verify`).
3. Hilfsfunktion `rssg_feed_name(string $url): string` (Anzeigename aus URL).

### B. Controller verschlanken
1. `feeds_verwalten.php`, `feed_hinzufuegen.php`, `feed_bearbeiten.php`,
   `ausgabe.php`, `graber_ajax.php`, `login.php` nutzen die Repositories statt
   inline-SQL. CSRF-/Auth-/Render-Logik bleibt im Controller.

### C. Tests
1. `FeedRepositoryTest` + `AdminRepositoryTest` (Integration gegen Test-DB)
   decken alle Methoden ab → reale Coverage der `classes/`-Schicht.
2. Unit-Test für `rssg_feed_name()`.

## Akzeptanzkriterien
- [ ] PHPStan Level 8 grün.
- [ ] PHPUnit grün, Coverage `classes/` ≥ 80 %.
- [ ] Playwright-E2E (16) grün, `php-error.log` leer.
- [ ] Kein inline-SQL mehr in den Controllern (außer Render).

## Nicht-Ziele
- Kein ORM, kein Framework, keine DB-Abstraktion über mysqli hinaus.
