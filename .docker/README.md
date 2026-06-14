# Docker-Entwicklungsumgebung – RSS Grabber

Lokale Entwicklungsumgebung für den RSS Grabber mit Web-Server, Datenbank und
Mail-Server. Alle Dienste sind unter dem Compose-Projektnamen **`rss-grabber`**
gekapselt und beeinträchtigen keine anderen Docker-Stacks auf dem Host.

## Dienste & Ports

| Dienst         | Container                | Host-Port | Beschreibung                          |
|----------------|--------------------------|-----------|---------------------------------------|
| Web-Server     | `rss-grabber_web`        | **8340**  | Apache + PHP 8.5 → http://localhost:8340 |
| Datenbank      | `rss-grabber_db`         | **3350**  | MySQL 8.0 (Host-Port → 3306 intern)   |
| phpMyAdmin     | `rss-grabber_phpmyadmin` | **8341**  | DB-Verwaltung → http://localhost:8341 |
| Mailpit (UI)   | `rss-grabber_mailpit`    | **8342**  | Mail-Postfach → http://localhost:8342 |
| Mailpit (SMTP) | `rss-grabber_mailpit`    | **1140**  | SMTP-Eingang (intern Port 1025)       |

Die Ports wurden so gewählt, dass sie mit keinem anderen laufenden Container
kollidieren. Bei einem Konflikt wird der jeweilige Port um mindestens 8 nach
oben verschoben.

## Netzwerk

Eigenes Bridge-Netzwerk mit **explizitem Subnetz `10.124.0.0/24`**.

Hintergrund: Dockers Standard-Adresspool (`172.17.0.0`–`172.31.0.0`) ist auf
diesem Host durch andere Stacks erschöpft. Das ursprünglich vorgesehene
`10.123.0.0/24` ist bereits durch `terminverwaltungs-script` belegt, daher
`10.124.0.0/24`.

## PHP-Version & Extensions

- **PHP 8.5** (`php:8.5-apache`) – Vorgabe: mindestens 8.5.
- Installierte Zusatz-Extensions: `mysqli`, `gd`, `intl`, `mbstring`, `zip`.
- `simplexml`, `dom` und `iconv` (RSS-Parsing + Zeichenkonvertierung) sind im
  offiziellen Image bereits standardmäßig aktiv.
- `allow_url_fopen = On` ist gesetzt, da Feeds per `fopen()` von externen URLs
  geladen werden.

> **Hinweis zu PHP 8.5:** `pdo`/`pdo_mysql` und `opcache` werden **nicht** über
> `docker-php-ext-install` gebaut. PDO wird nicht verwendet, und unter PHP 8.5
> sind PDO (statisch) sowie Zend OPcache (fest ins Core kompiliert) nicht mehr
> als Shared-Module installierbar. Die `opcache.*`-Direktiven in `php.ini`
> wirken dennoch.

## Datenbank-Zugang

| Feld       | Wert                  |
|------------|-----------------------|
| Host       | `db` (innerhalb Docker) / `localhost:3350` (vom Host) |
| Datenbank  | `rss_grabber`         |
| Benutzer   | `rss_grabber`         |
| Passwort   | `rss_grabber_secret`  |
| Root-PW    | `root`                |

Diese Werte sind in `inc/config.php` für die Docker-Umgebung bereits
hinterlegt – die App ist ohne manuellen Installationslauf sofort nutzbar.

### Init-Skript

`init.sql` wird beim **ersten** Start des `db`-Containers automatisch
ausgeführt (`docker-entrypoint-initdb.d`). Es legt die Tabellen `feeds` und
`feeds_post` an und befüllt `feeds` mit drei Beispiel-Feeds – analog zur
Installationsroutine in `install/index.php`.

> Das Init-Skript läuft nur, solange das DB-Volume leer ist. Zum erneuten
> Ausführen das Volume entfernen (siehe unten).

## PHP-Fehlerlog ("Php Hourlog")

Das PHP-Error-Log wird per Volume direkt ins `.docker`-Verzeichnis auf dem
Windows-Rechner gespiegelt:

```
.docker/php-error.log   ←→   /var/log/php-error.log (Container)
```

So sind PHP-Fehler ohne `docker exec` direkt im Dateisystem einsehbar.

## Bedienung

Alle Befehle aus dem `.docker`-Verzeichnis ausführen.

```bash
# Stack bauen & starten
docker compose up -d --build

# Status anzeigen
docker compose ps

# Logs verfolgen (z. B. Web)
docker compose logs -f web

# Stoppen (Daten bleiben erhalten)
docker compose down

# Stoppen inkl. DB-Volume (setzt die Datenbank zurück,
# Init-Skript läuft beim nächsten Start erneut)
docker compose down -v
```

## Mail

Ausgehende Mails werden über `msmtp` an Mailpit zugestellt und landen **nicht**
im echten Postausgang. Eingang prüfen unter http://localhost:8342.

## Dateien

| Datei                | Zweck                                                  |
|----------------------|--------------------------------------------------------|
| `docker-compose.yml` | Service-Definition (Name, Ports, Netzwerk, Volumes)    |
| `Dockerfile`         | Web-Image (PHP 8.5 + Apache + Extensions + msmtp)      |
| `php.ini`            | PHP-Konfiguration (Fehlerlog, Limits, Mail, Zeitzone)  |
| `init.sql`           | Automatischer DB-Init (Tabellen + Beispiel-Feeds)      |
| `php-error.log`      | Gespiegeltes PHP-Fehlerlog                             |
