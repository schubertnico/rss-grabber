# Umsetzungsplan – Repository-Schicht & Scroll-Bugfix

1. `classes/FeedRepository.php` (mysqli injiziert): alle Feed-/Post-/Sync-Queries
   (Prepared Statements; ID-Listen per `intval`).
2. `classes/AdminRepository.php`: `verifyLogin()`.
3. `classes/function.php`: `rssg_feed_name()`.
4. Controller verschlanken: `login`, `feeds_verwalten`, `feed_hinzufuegen`,
   `feed_bearbeiten`, `ausgabe`, `graber_ajax` nutzen die Repositories.
5. **Bugfix** `ausgabe.php`: AJAX-Erkennung über `array_key_exists('ajax', $_POST)`
   statt `(int)$ajax !== 0` – sonst liefert `ajax=0` das ganze Layout.
6. Tests: `FeedRepositoryTest`, `AdminRepositoryTest`, `rssg_feed_name`-Unit,
   E2E-Fragment-Test.
7. Gates: PHPStan Level 8, PHPUnit (Coverage ≥ 80 %), Playwright, Log leer.
8. Doku; Commit → Merge `main` → Push → Branch löschen.
