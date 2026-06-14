# Spezifikation – Robustheit & Code-Qualität (Runde 2)

## Ziel

Härtung gegen Ausfälle und Aufräumen technischer Schulden, ohne die
Anwenderfunktion zu ändern.

## Anforderungen

### A. Synchronisierung robust (`graber_ajax.php`)
1. Feed-Abruf mit **Timeout** (Stream-Context, 10 s), damit ein hängender Feed
   den Lauf nicht blockiert.
2. **Kein `die()`** mehr im Sync: ein einzelner defekter Feed/DB-Fehler bricht
   nicht den gesamten Lauf ab (Best-Effort, Status `fehler`).

### B. Installer absichern (`install/index.php`)
1. Die generierte `config.php` wird mit `var_export` erzeugt → Werte mit `'`/`"`
   können die Datei nicht mehr brechen (kein Syntax-Bruch / Code-Injection).
2. Generierte Datei nutzt den modernen Schutz-Guard (`realpath(...) === __FILE__`)
   und `int`-Typen für numerische Einstellungen.
3. POST-Werte werden vor `trim()` auf `is_string` geprüft (kein `TypeError` bei
   Array-Eingabe).

### C. Kein Info-Leak (`db.php`)
1. Bei Verbindungsfehler: generische Meldung an den Client, Details via
   `error_log`, HTTP 500.

### D. Multibyte-sichere Kürzung (`classes/function.php`)
1. `limitch()` nutzt `mb_strlen`/`mb_substr` (UTF-8), schneidet keine Umlaute
   mitten durch.

### E. Entdopplung & Testbarkeit (`ausgabe.php`)
1. Die doppelte Beitrags-Render-Logik wird in `rssg_render_feed_post()`
   ausgelagert (zentrales Escaping, unit-testbar).
2. Leere Feed-ID-Liste erzeugt keine fehlerhafte `IN ('')`-Query.

## Akzeptanzkriterien
- [ ] PHPUnit grün inkl. neuer Tests (`rssg_render_feed_post`, mb-Kürzung),
      Coverage `classes/` ≥ 80 %.
- [ ] Playwright-E2E weiterhin grün.
- [ ] `php-error.log` bleibt leer.
- [ ] Installer erzeugt mit Passwort `a'b"c` eine syntaktisch gültige config.php.
