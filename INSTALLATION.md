# Installationsanleitung – RSS Grabber free v3.0

Diese Anleitung beschreibt die Installation der Version **3.0**. Sie ersetzt die
ältere `Installationsanleitung_2.00.pdf` (Version 2.0).

## 1. Voraussetzungen

| Komponente   | Anforderung                                            |
|--------------|--------------------------------------------------------|
| PHP          | **8.5** oder höher                                     |
| PHP-Module   | `mysqli`, `simplexml`, `iconv` (alle Standard)         |
| Datenbank    | MySQL/MariaDB mit **utf8mb4**                           |
| Webserver    | Apache mit `mod_rewrite` (oder vergleichbar)           |
| Sonstiges    | `allow_url_fopen = On` (zum Abrufen der Feeds)         |

> Läuft auf dem Server eine ältere PHP-Version als 8.5, bricht die
> Installationsroutine mit einem Hinweis ab.

## 2. Installation auf einem Webspace (Shared Hosting)

### Schritt 1 – Dateien hochladen
Laden Sie alle Projektdateien per FTP/SFTP in das gewünschte Verzeichnis Ihres
Webspace hoch (z. B. `/rss-grabber/`).

### Schritt 2 – Datenbank anlegen
Legen Sie über Ihr Hosting-Panel eine MySQL-Datenbank an (Zeichensatz
**utf8mb4**) und notieren Sie Host, Datenbankname, Benutzer und Passwort.

### Schritt 3 – Installationsroutine aufrufen
Öffnen Sie im Browser `https://IHRE-DOMAIN/rss-grabber/install/` und füllen Sie
das Formular aus:

- **Datenbankdaten**: Host (meist `localhost`), Datenbankname, Benutzer, Passwort.
- **Einstellungen**: Feeds pro Lauf, Einträge pro Seite, max. Beschreibungslänge,
  ISO→UTF-8 (Standard: 1).

Die Routine legt die Tabellen `feeds`, `feeds_post` und `admin` an, befüllt
Beispiel-Feeds und erzeugt die Datei `inc/config.php`.

### Schritt 4 – Install-Verzeichnis entfernen
Löschen Sie nach erfolgreicher Installation das Verzeichnis `install/` vom Server.

### Schritt 5 – Anmelden
Der Verwaltungsbereich ist jetzt durch ein **Login** geschützt. Öffnen Sie
`https://IHRE-DOMAIN/rss-grabber/` und melden Sie sich an:

| Feld     | Standardwert |
|----------|--------------|
| Benutzer | `admin`      |
| Passwort | `admin`      |

> ⚠️ **Ändern Sie das Standardpasswort umgehend.** Erzeugen Sie dazu einen neuen
> bcrypt-Hash, z. B. mit
> `php -r "echo password_hash('IHR-NEUES-PASSWORT', PASSWORD_DEFAULT);"`, und
> tragen Sie ihn in die Tabelle `admin` (Spalte `password_hash`) ein.

Öffentlich erreichbar bleibt nur die Beitragsanzeige (`ausgabe.php`).

## 3. Nutzung

1. **Neuen Feed eintragen** – Homepage und Feed-URL (RSS 2.0 oder Atom) angeben.
2. **Feeds synchronisieren** – ruft die Feeds ab und speichert neue Beiträge.
3. **Alle Feeds anzeigen** – zeigt die Beiträge (mit automatischem Nachladen
   beim Scrollen).
4. **Feeds verwalten** – Feeds bearbeiten oder löschen.

Richten Sie die Synchronisierung idealerweise als regelmäßigen Cron-Job ein, der
`feeds_synchronisieren.php` bzw. den Sync-Endpunkt aufruft.

## 4. Sicherheit nach der Installation

- Standard-Admin-Passwort ändern (siehe oben).
- `inc/config.php` enthält Zugangsdaten – per Server/`.htaccess` vor direktem
  Abruf schützen.
- Install-Verzeichnis entfernt halten.

## 5. Installation per Docker (Entwicklung)

Für lokale Entwicklung liegt eine fertige Umgebung unter `.docker/` bereit
(Web, Datenbank, Mailpit, phpMyAdmin):

```bash
cd .docker
docker compose up -d --build
```

| Dienst     | URL                    |
|------------|------------------------|
| Anwendung  | http://localhost:8340  |
| phpMyAdmin | http://localhost:8341  |
| Mailpit    | http://localhost:8342  |

Die Datenbank wird über `.docker/init.sql` automatisch eingerichtet
(inkl. Admin-Zugang `admin`/`admin`); `inc/config.php` ist vorkonfiguriert.
Details: [`.docker/README.md`](.docker/README.md).

## 6. Fehlerbehebung

| Problem                          | Ursache / Lösung                              |
|----------------------------------|-----------------------------------------------|
| Weiterleitung auf `install/`     | `inc/config.php` fehlt – Installation ausführen|
| „Datenbankverbindung nicht möglich" | DB-Zugangsdaten in `inc/config.php` prüfen  |
| Umlaute falsch dargestellt       | Datenbank/Tabellen müssen `utf8mb4` sein       |
| Feeds werden nicht abgerufen     | `allow_url_fopen` aktivieren; URL erreichbar?  |
| PHP-Version-Hinweis im Installer | PHP auf 8.5+ aktualisieren                     |
