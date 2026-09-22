# Spezifikation – Abschluss des Release 3.0

Stand: 22.09.2026

Die Modernisierung auf Version 3.0 ist inhaltlich abgeschlossen (siehe
`CHANGELOG.md`). Offen ist die **Auslieferung**: Der Git-Tag hängt hinter dem
aktuellen Stand, eine Versionsangabe ist veraltet, die Abnahme ist nicht belegt,
und die öffentliche Produktseite auf php-space.info bewirbt weiterhin die
Version 2.0 von 2022.

## Ausgangslage

| Befund | Ist | Soll |
|---|---|---|
| Tag `v3.0.0` | zeigt auf `57b574b`, 12 Commits hinter `main` | zeigt auf den ausgelieferten Stand |
| `composer.json` | `description` nennt `free v2.0` | `free v3.0` |
| `tpl/layout.html` | `© 2011 by http://www.php-space.info` | Jahr aktuell, `https` |
| Abnahme | 28 Kriterien in `docs/SPECS*.md` unabgehakt, Container seit 3 Monaten aus | Testläufe belegt, Kriterien abgehakt |
| GitHub-Release | keines vorhanden | Release `v3.0.0` mit ZIP und Notes |
| Produktseite `/rss-grabber/` | PHP 8.1, Download 2.00, Screenshots von 2011 | Version 3.0 durchgängig |
| Installationsanleitung | unbebildert, empfiehlt Rechte 777 zuerst | bebildert, sichere Rechte-Empfehlung |

## Anforderungen

### A – Repository

- **A1** `composer.json` nennt die Version 3.0.
- **A2** Die Copyright-Zeile in `tpl/layout.html` nennt den Zeitraum 2011–2026
  und verweist per `https` auf php-space.info.
- **A3** PHPUnit, PHPStan (Level 8) und Playwright laufen gegen den aktuellen
  Stand im Docker und sind grün; `php-error.log` bleibt leer.
- **A4** Die Abnahmekriterien in allen `docs/SPECS*.md` sind abgehakt, sobald
  der zugehörige Lauf nachgewiesen ist.
- **A5** Der Tag `v3.0.0` zeigt auf den Stand, der ausgeliefert wird – lokal
  und auf `origin`.
- **A6** Ein GitHub-Release `v3.0.0` existiert mit den Notes aus dem CHANGELOG
  und dem Release-ZIP als Anhang.

### B – Screenshots

- **B1** Die Demo-Screenshots zeigen nur den Seiteninhalt, ohne den
  Leerraum unterhalb (bisher bis zu 50 % der Bildhöhe).
- **B2** Aus den Screenshots entstehen die Bilder für die Produktseite im dort
  üblichen Format: Vollbild 890 px breit, Vorschaubild 200 × 200 px, abgelegt
  unter `img/` von php-space.info nach dem Namensschema
  `<thema>_rss_grabber_3.0(_t).jpg`.

  *Abgelöst am 22.09.2026:* Die Produktseite folgt jetzt dem Muster von
  `/simple-php-forum/` – PNG, Vollbild 1200 px breit, Vorschau 280 px breit
  mit natürlicher Höhe, abgelegt unter `rss-grabber/bilder/` und
  `rss-grabber/bilder/tn/tn_*`, Anzeige in einer Lightbox. Aufgenommen mit
  `SKALIERUNG=2` (`build/screenshots.mjs`), damit das Vollbild scharf bleibt.

### C – Installationsanleitung

- **C1** Die Anleitung ist bebildert: Installationsformular, Anmeldung,
  Feed anlegen, Synchronisieren, Beitragsanzeige.
- **C2** Die Rechte-Empfehlung nennt zuerst 755/644 und 777 nur als Ausweg,
  wenn der Hoster nichts anderes zulässt.
- **C3** Ein Abschnitt beschreibt den **Umstieg von Version 2.0 auf 3.0**
  (Datenbank, neue Tabelle `admin`, entfallene JavaScript-Dateien).
- **C4** Ein Abschnitt „Bedienung" führt durch den ersten Feed.
- **C5** Eine Tabelle zur Fehlersuche deckt die häufigen Fälle ab.
- **C6** Das Datum der Anleitung entspricht dem Release-Datum.
- **C7** `INSTALLATION.md` und das PDF bleiben inhaltlich deckungsgleich.

### D – Produktseite php-space.info/rss-grabber/

- **D1** Die genannte PHP-Mindestversion ist 8.5.
- **D2** Die Download-Verweise zeigen auf Version 3.00 (ZIP und RAR), die
  bisherigen Fassungen bleiben als ältere Versionen erreichbar.
- **D3** Der Verweis auf die Installationsanleitung zeigt auf die Fassung 3.0.
- **D4** Die Script-History nennt die Version 3.0 mit ihren Änderungen.
- **D5** Die Screenshots zeigen die Oberfläche der Version 3.0.
- **D6** Der Text nennt die Neuerungen der Version 3.0 (Login, UTF-8,
  Atom, kein jQuery/prototype).
- **D7** Das Einrichtungsvideo von 2011 ist als solches gekennzeichnet.

## Abnahmekriterien

- [x] `vendor/bin/phpunit` grün, Coverage `classes/` ≥ 80 %.
- [x] `vendor/bin/phpstan analyse` → `[OK] No errors`.
- [x] Playwright-E2E grün, `php-error.log` leer.
- [x] `git describe --tags` auf `main` liefert `v3.0.0` ohne Abstand.
- [x] `gh release view v3.0.0` zeigt das Release mit angehängtem ZIP.
- [x] Das ZIP enthält keine Tests, keine Docker-Dateien und keine `config.php`.
- [x] Die Produktseite rendert lokal im php-space-Container ohne PHP-Fehler
      und nennt nirgends mehr die Version 2.00 als aktuelle Fassung.
- [x] Alle neuen Bilddateien sind unter `img/` vorhanden und über die
      `images_`-Rewrite-Regel abrufbar.
