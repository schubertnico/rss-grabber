# Spezifikation – Frontend-Modernisierung + CSRF-Sync

## Ziel

Ablösung der veralteten JavaScript-Bibliotheken (prototype.js, jQuery 1.4.2 von
2010) durch schlankes, abhängigkeitsfreies Vanilla-JS bei **gleichem Verhalten**.
Zusätzlich wird der AJAX-Synchronisierungs-Aufruf gegen CSRF abgesichert.

## Anforderungen

### A. JavaScript ohne Fremd-Bibliotheken
1. Neue Datei `java/rss-grabber.js` (Vanilla JS, IIFE, `fetch`) übernimmt:
   - **Sync** (`feeds_synchronisieren.php`): „Jetzt Synchronisieren" pollt
     `graber_ajax.php` und aktualisiert `#update`, bis „Fertig".
   - **Endless-Scroll** (`ausgabe.php`): beim Scrollen ans Seitenende werden
     weitere Beiträge per POST `ausgabe.php` (`ajax=N`) nachgeladen, bis die in
     `#anz` hinterlegte Seitenzahl erreicht ist.
2. `tpl/layout.html` lädt nur noch `rss-grabber.js`; der bisherige Inline-Code
   und die `<script>`-Einbindungen von prototype.js/jQuery.js entfallen.
3. Die Dateien `java/prototype.js`, `java/jQuery.js`, `java/jquery-1.4.2.min.js`
   werden gelöscht.

### B. CSRF-Schutz für die Synchronisierung
1. `graber_ajax.php` prüft ein CSRF-Token (`POST csrf`) nach dem Login-Check und
   weist ungültige Anfragen mit HTTP 403 ab.
2. Das Token wird über ein `data-csrf`-Attribut des Sync-Auslösers an das JS
   übergeben (`feeds_synchronisieren.html` / `feeds_synchronisieren.php`).

## Akzeptanzkriterien
- [ ] Keine Referenzen auf prototype/jQuery mehr; die drei Alt-Dateien sind weg.
- [ ] Alle Seiten laden ohne JS-Fehler (Playwright `pageerror`).
- [ ] Sync-Klick aktualisiert `#update` mit einem Status (E2E).
- [ ] `graber_ajax.php` ohne Token → 403 (E2E).
- [ ] PHPUnit weiterhin grün (≥80 %), `php-error.log` leer.

## Nicht-Ziele
- Optisches Redesign, CSS-Überarbeitung, Build-Tooling/Bundler.
