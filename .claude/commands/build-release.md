---
description: Baut ein Release-ZIP der aktuellen Version (ohne Docker, Dev-/Test-Konfigurationen und Tests) und erzeugt den passenden Git-Tag
argument-hint: "[version]"
---

Baue ein Release-ZIP des RSS Grabbers fuer die Auslieferung an Endnutzer und
erzeuge den zugehoerigen Git-Tag.

## Schritte

1. Fuehre das Build-Skript aus (PowerShell, kein Docker/PHP noetig). Es baut das
   ZIP, erzeugt den annotierten Git-Tag `v<version>` und pusht ihn nach origin.
   Wenn ein Versionsargument uebergeben wurde, reiche es weiter:

   ```
   powershell -ExecutionPolicy Bypass -File build/build-release.ps1 -Tag -Push -Version "$ARGUMENTS"
   ```

   Ohne Argument das Skript ohne `-Version` aufrufen (Version wird aus
   `inc/config.php` ermittelt). Existiert der Tag bereits, wird er uebersprungen.

2. Verifiziere den Inhalt des erzeugten Archivs `build/rss-grabber-v<version>.zip`:
   liste die enthaltenen Pfade und pruefe, dass **nicht** enthalten sind:
   `.docker/`, `tests/`, `docs/`, `build/`, `vendor/`, `node_modules/`,
   `inc/config.php`, `composer.*`, `phpstan.neon`, `phpunit.xml`,
   `package*.json`, `playwright.config.ts`.

   Und dass **enthalten** sind: die Controller-PHP-Dateien, `classes/`, `inc/auth.php`,
   `install/`, `tpl/`, `css/`, `img/`, `java/`, `.htaccess`, `INSTALLATION.md`,
   die Installationsanleitung-PDF und `LICENSE`.

3. Pruefe, dass der Git-Tag gesetzt ist (SemVer, z. B. `git tag --list v3.0.0`).

4. Berichte: Pfad des ZIPs, Versionsnummer, Dateianzahl, Groesse und den
   erzeugten/gepushten Git-Tag. Liefere das ZIP per SendUserFile aus.

## Hinweise

- Der Git-Tag folgt SemVer `vX.Y.Z` (aus der Version in `inc/config.php`
  abgeleitet, z. B. `3.0` -> Tag `v3.0.0`). Der ZIP-Name nutzt die kuerzere
  Anzeige-Version (z. B. `rss-grabber-v3.0.zip`). Soll eine andere Version
  verwendet werden, beim Aufruf ein Argument uebergeben: `/build-release 3.1`.
- Das ZIP enthaelt eine fertige Verzeichnisstruktur unter `rss-grabber/`. Der
  Endnutzer laedt den Inhalt auf seinen Webspace und ruft `install/` auf
  (siehe `INSTALLATION.md`). Das Build-Artefakt selbst wird nicht eingecheckt
  (`.gitignore`).
