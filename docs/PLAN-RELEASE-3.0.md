# Umsetzungsplan – Abschluss des Release 3.0

Zur Spezifikation: [`SPECS-RELEASE-3.0.md`](SPECS-RELEASE-3.0.md).
Gearbeitet wird auf dem Zweig `feature/release-v3.0-abschluss`, der am Ende
mit `--no-ff` nach `main` geht.

Zwei Arbeitsbereiche, zwei Repositories:

| Bereich | Repository |
|---|---|
| Script, Anleitung, Release | `rss-grabber/rss-grabber_git` |
| Produktseite, Bilder, Downloads | `php-space.info` (Wurzel) |

## Schritt 1 – Versionsangaben korrigieren

- `composer.json`: `description` auf `free v3.0` (A1).
- `tpl/layout.html`: Copyright auf `2011–2026`, `https` (A2).

## Schritt 2 – Screenshots verbessern

- `build/screenshots.mjs` schneidet auf den Inhalt zu, statt ganze Seiten mit
  Leerraum abzubilden (B1). Für die Seiten mit Layout ist das der Container
  `.layout`, für die Installationsroutine deren äußere Tabelle.
- Die Screenshots neu erzeugen.

## Schritt 3 – Installationsanleitung überarbeiten

- `build/installation-anleitung.html` erhält Abbildungen, den Abschnitt zum
  Umstieg von 2.0, einen Bedienabschnitt, eine Fehlertabelle und eine
  Rechte-Empfehlung, die mit 755/644 beginnt (C1–C6).
- `INSTALLATION.md` auf denselben Stand bringen (C7).
- PDF über `build/html-to-pdf.mjs` neu erzeugen.

## Schritt 4 – Abnahme im Docker

Reihenfolge, weil jeder Lauf den nächsten voraussetzt:

1. `docker compose up -d --build` in `.docker/`.
2. `vendor/bin/phpunit --coverage-text`
3. `vendor/bin/phpstan analyse`
4. Playwright im offiziellen Image im Projektnetzwerk.
5. `.docker/php-error.log` prüfen.

Erst danach die Abnahmekriterien in den `docs/SPECS*.md` abhaken (A3, A4).

## Schritt 5 – Bilder für die Produktseite

- Aus `Screenshots/v3.0/*.png` die Bilder für php-space.info erzeugen:
  Vollbild 890 px breit, Vorschau 200 × 200 px, JPEG (B2).
- Ablage unter `img/` von php-space.info, Namen nach bestehendem Schema mit
  dem Zusatz `3.0`.

## Schritt 6 – Produktseite überarbeiten

`rss-grabber/index.php` im php-space.info-Repository (D1–D7):
PHP-Anforderung, Downloads, Anleitung, History, Screenshots, Neuerungstext,
Kennzeichnung des alten Videos.

## Schritt 7 – Paket und Downloads

- Release-ZIP über `build/build-release.ps1` neu bauen.
- Kopien im Downloadverzeichnis ablegen: `rss_grabber3.00_free.zip` und
  `.rar`, passend zum Namensschema der Vorgängerversionen.

## Schritt 8 – Veröffentlichen

- Tag `v3.0.0` auf den Merge-Commit setzen, auch auf `origin` (A5).
- GitHub-Release `v3.0.0` mit Notes aus dem CHANGELOG und ZIP als Anhang (A6).
- Im php-space.info-Repository nur die Dateien dieses Vorhabens committen –
  dort liegen unabhängige Änderungen anderer Arbeiten im Arbeitsverzeichnis.

## Bewusst nicht Teil dieses Vorhabens

- **Ein neues Einrichtungsvideo.** Das vorhandene zeigt die Version 1.0. Ein
  Neudreh ist ein eigenes Vorhaben; bis dahin wird das Video gekennzeichnet.
- **Passwortvergabe in der Installationsroutine.** Der Standardzugang
  `admin`/`admin` mit Änderungspflicht bleibt für 3.0 bestehen; eine Abfrage im
  Installer wäre eine Funktionsänderung nach dem Release-Stand.
- **Ausliefern auf den Server.** Das Deployment von php-space.info erfolgt
  getrennt über das dortige Deployment-System.
