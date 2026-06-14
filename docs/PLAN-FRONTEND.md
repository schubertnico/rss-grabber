# Umsetzungsplan – Frontend-Modernisierung + CSRF-Sync

1. **`java/rss-grabber.js`** (neu): Vanilla-JS für Sync-Polling und
   Endless-Scroll (`fetch`, `URLSearchParams`).
2. **`tpl/layout.html`**: prototype.js/jQuery.js + Inline-JS entfernen, nur noch
   `rss-grabber.js` einbinden.
3. **`tpl/feeds_synchronisieren.html`**: Auslöser auf
   `data-sync-trigger data-csrf="{CSRF}"` umstellen.
4. **`feeds_synchronisieren.php`**: `$lang_formular['csrf']` setzen.
5. **`graber_ajax.php`**: CSRF-Token prüfen (403 bei Fehlen).
6. **Löschen**: `java/prototype.js`, `java/jQuery.js`, `java/jquery-1.4.2.min.js`.
7. **Tests**: E2E um Sync-Flow + CSRF-Negativtest erweitern; PHPUnit-Regression.
8. **Abschluss**: Doku, Commit → Merge `main` → Push → Branch löschen.
