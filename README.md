# RSS Grabber free v3.0

Ein schlankes PHP-Skript, das RSS-2.0- und Atom-Feeds einliest, deren Beiträge
in einer MySQL-Datenbank speichert und anzeigt.

> Modernisiert für **PHP 8.5** mit durchgängigem **UTF-8** (Umlaute ä ö ü ß
> ohne Mojibake) sowie automatisierten **PHPUnit-** und **Playwright-Tests**.

## Anforderungen

- PHP **8.5** oder höher (Extensions: `mysqli`, `simplexml`, `iconv`)
- MySQL/MariaDB mit `utf8mb4`
- Apache mit `mod_rewrite`

## Schnellstart (Docker)

Die vollständige Entwicklungsumgebung (Web, DB, Mailpit, phpMyAdmin) liegt unter
[`.docker/`](.docker/README.md).

```bash
cd .docker
docker compose up -d --build
```

| Dienst      | URL                          |
|-------------|------------------------------|
| Anwendung   | http://localhost:8340        |
| phpMyAdmin  | http://localhost:8341        |
| Mailpit     | http://localhost:8342        |

Die Datenbank wird beim ersten Start über `.docker/init.sql` automatisch mit den
Tabellen `feeds`/`feeds_post`/`admin` (utf8mb4) und Beispiel-Feeds befüllt;
`inc/config.php` ist passend vorkonfiguriert. Die Anwendung ist sofort nutzbar.

### Anmeldung

Der Verwaltungsbereich (Feeds anlegen/bearbeiten/löschen, Synchronisierung) ist
durch ein Login geschützt. Öffentlich erreichbar ist nur die Beitragsanzeige
(`ausgabe.php`).

| Feld     | Wert    |
|----------|---------|
| Benutzer | `admin` |
| Passwort | `admin` |

> ⚠️ **Das Default-Passwort nach der Installation umgehend ändern** (Hash in der
> Tabelle `admin` aktualisieren).

## Aufbau

| Pfad                        | Zweck                                            |
|-----------------------------|--------------------------------------------------|
| `index.php`                 | Einstieg → Weiterleitung auf `ausgabe.php`       |
| `ausgabe.php`               | Anzeige der Beiträge (mit AJAX-Endless-Scroll)   |
| `feeds_verwalten.php`       | Feeds auflisten / löschen                        |
| `feed_hinzufuegen.php`      | Feed anlegen                                     |
| `feed_bearbeiten.php`       | Feed bearbeiten                                  |
| `feeds_synchronisieren.php` | Synchronisierung (UI)                            |
| `graber_ajax.php`           | Synchronisierung (AJAX-Backend)                  |
| `classes/function.php`      | `limitch`, `date_mysql2german`, `addItem`        |
| `classes/parase.php`        | Template-Parser `PARSE`                          |
| `install/index.php`         | Erstinstallation                                 |

## Tests

### PHPUnit (Unit + Integration)

Läuft im Web-Container (DB-Host `db` erreichbar):

```bash
docker exec rss-grabber_web bash -c "cd /var/www/html && vendor/bin/phpunit --coverage-text"
```

- **Unit:** `limitch`, `date_mysql2german`, `PARSE`
- **Integration:** `addItem()` gegen eine Test-DB inkl. UTF-8-Roundtrip;
  Controller-Smoke-Tests (jede Seite rendert ohne PHP-Fehler)
- **Coverage:** ≥ 80 % über `classes/` (aktuell ~94 %)

### Statische Analyse (PHPStan)

```bash
docker exec rss-grabber_web bash -c "cd /var/www/html && vendor/bin/phpstan analyse"
```

PHPStan **Level 8**, Lauf ist fehlerfrei. Kurzform: `composer analyse`.

### Playwright (E2E)

Gegen die laufende Docker-Instanz (offizielles Playwright-Image im
rss-grabber-Netzwerk):

```bash
docker run --rm --network rss-grabber_rss-grabber-network \
  -e BASE_URL=http://web \
  -v "$PWD:/work" -w /work \
  mcr.microsoft.com/playwright:v1.50.0-noble \
  bash -c "npm install && npx playwright test"
```

Prüft: keine 4xx/5xx (auch Assets), keine PHP-Fehler im HTML, keine
JS-Fehler, Navigation, korrekte Umlaut-Darstellung und der CRUD-Fluss.

## Dokumentation

- [`INSTALLATION.md`](INSTALLATION.md) – **Installationsanleitung (v3.0)**
- [`CHANGELOG.md`](CHANGELOG.md) – Release Notes (v3.0.0)
- [`docs/SPECS.md`](docs/SPECS.md) – Spezifikation der Modernisierung
- [`docs/PLAN.md`](docs/PLAN.md) – Umsetzungsplan
- [`.docker/README.md`](.docker/README.md) – Docker-Umgebung
