# Umsetzungsplan – Robustheit & Code-Qualität (Runde 2)

1. **`classes/function.php`**
   - `limitch()` auf `mb_strlen`/`mb_substr` (UTF-8) umstellen.
   - `rssg_render_feed_post(array $row, array $feeds, int $maxDesc): string`
     hinzufügen (zentrales Escaping, ausgelagert aus `ausgabe.php`).
2. **`ausgabe.php`**
   - Beide Ausgabe-Schleifen nutzen `rssg_render_feed_post()`.
   - ID-Liste per `intval`; leere Liste → Query überspringen/`0`.
3. **`graber_ajax.php`**
   - Feed-Fetch über Stream-Context mit Timeout + `simplexml_load_string`.
   - Kein `die()` mehr; Best-Effort pro Feed.
4. **`db.php`**
   - Verbindungsfehler: `error_log` + generische Meldung + HTTP 500.
5. **`install/index.php`**
   - `config.php` per `var_export`; POST-Werte `is_string`-geprüft.
6. **Tests**
   - `tests/Unit/FunctionsTest.php`: mb-Kürzung + `rssg_render_feed_post`.
   - PHPUnit + Playwright im Docker; `php-error.log` leer.
7. **Abschluss**
   - README/CHANGELOG; Commit → Merge `main` → Push → Branch löschen.
