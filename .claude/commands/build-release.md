---
description: Baut ein verteilbares Release-ZIP der aktuellen Version (ohne Docker, Dev-/Test-Konfigurationen und Tests)
argument-hint: "[version]"
---

Baue ein Release-ZIP des RSS Grabbers fuer die Auslieferung an Endnutzer.

## Schritte

1. Fuehre das Build-Skript aus (PowerShell, kein Docker/PHP noetig). Wenn ein
   Versionsargument uebergeben wurde, reiche es weiter:

   ```
   powershell -ExecutionPolicy Bypass -File build/build-release.ps1 -Version "$ARGUMENTS"
   ```

   Ohne Argument das Skript ohne `-Version` aufrufen (Version wird aus
   `inc/config.php` ermittelt).

2. Verifiziere den Inhalt des erzeugten Archivs `build/rss-grabber-v<version>.zip`:
   liste die enthaltenen Pfade und pruefe, dass **nicht** enthalten sind:
   `.docker/`, `tests/`, `docs/`, `build/`, `vendor/`, `node_modules/`,
   `inc/config.php`, `composer.*`, `phpstan.neon`, `phpunit.xml`,
   `package*.json`, `playwright.config.ts`.

   Und dass **enthalten** sind: die Controller-PHP-Dateien, `classes/`, `inc/auth.php`,
   `install/`, `tpl/`, `css/`, `img/`, `java/`, `.htaccess`, `INSTALLATION.md`,
   die Installationsanleitung-PDF und `LICENSE`.

3. Berichte: Pfad des ZIPs, Versionsnummer, Dateianzahl und Groesse. Liefere das
   ZIP per SendUserFile aus.

## Hinweis

Das ZIP enthaelt eine fertige Verzeichnisstruktur unter `rss-grabber/`. Der
Endnutzer laedt den Inhalt auf seinen Webspace und ruft `install/` auf
(siehe `INSTALLATION.md`). Das Build-Artefakt selbst wird nicht eingecheckt
(`.gitignore`).
