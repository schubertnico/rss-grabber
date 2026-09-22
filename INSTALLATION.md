# Installationsanleitung – RSS Grabber free v3.0

Diese Anleitung beschreibt die Installation der Version **3.0**. Sie ersetzt die
ältere `Installationsanleitung_2.00.pdf` (Version 2.0).

> Dieselbe Anleitung liegt dem Paket als **bebildertes PDF** bei:
> `Installationsanleitung_3.0.pdf`.

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

## 2. Das ist neu in der Version 3.0

- **Anmeldung für den Verwaltungsbereich.** Feeds anlegen, bearbeiten, löschen
  und synchronisieren setzt eine Anmeldung voraus. Öffentlich erreichbar bleibt
  nur die Beitragsanzeige (`ausgabe.php`).
- **Umlaute werden korrekt gespeichert** – Datenbank und Ausgabe laufen
  durchgängig auf `utf8mb4`.
- **PHP 8.5** ohne Warnungen und veraltete Aufrufe.
- **Kein jQuery und kein prototype.js mehr**; das Nachladen beim Scrollen
  erledigt eine schlanke eigene JavaScript-Datei.
- **Abgesicherte Eingaben** über vorbereitete Anweisungen und maskierte Ausgaben.

## 3. Installation auf einem Webspace (Shared Hosting)

### Schritt 1 – Datenbank anlegen
Legen Sie über Ihr Hosting-Panel eine MySQL-Datenbank an (Zeichensatz
**utf8mb4**) und notieren Sie Host, Datenbankname, Benutzer und Passwort.

### Schritt 2 – Dateien hochladen
Laden Sie alle Projektdateien per FTP/SFTP in das gewünschte Verzeichnis Ihres
Webspace hoch (z. B. `/rss-grabber/`).

### Schritt 3 – Schreibrecht für `inc/` sicherstellen
Die Installationsroutine legt dort die Datei `config.php` an. Bei den meisten
Hostern funktioniert das ohne Zutun. Erst wenn die Routine meldet, dass sie die
Datei nicht schreiben kann, greifen Sie ein – in dieser Reihenfolge:

1. **755 für Verzeichnisse, 644 für Dateien.** Die richtige Einstellung für
   einen normal eingerichteten Webserver.
2. **775 für `inc/`**, wenn der Webserver unter einer anderen Kennung läuft,
   aber zur selben Gruppe gehört.
3. **777 nur im Notfall** – und nach der Installation wieder auf 755 zurück.
   Die Konfigurationsdatei ist dann geschrieben und wird nicht mehr verändert.

### Schritt 4 – Installationsroutine aufrufen
Öffnen Sie im Browser `https://IHRE-DOMAIN/rss-grabber/install/` und füllen Sie
das Formular aus:

- **Datenbankdaten**: Host (meist `localhost`), Datenbankname, Benutzer, Passwort.
- **Einstellungen**: Feeds pro Lauf, Einträge pro Seite, max. Beschreibungslänge,
  ISO→UTF-8 (Standard: 1).

Die Routine legt die Tabellen `feeds`, `feeds_post` und `admin` an, befüllt
Beispiel-Feeds und erzeugt die Datei `inc/config.php`.

### Schritt 5 – Install-Verzeichnis entfernen
Löschen Sie nach erfolgreicher Installation das Verzeichnis `install/` vom
Server. Solange es erreichbar ist, kann jeder Ihre Installation überschreiben.

### Schritt 6 – Anmelden
Öffnen Sie `https://IHRE-DOMAIN/rss-grabber/` und melden Sie sich an:

| Feld     | Standardwert |
|----------|--------------|
| Benutzer | `admin`      |
| Passwort | `admin`      |

> ⚠️ **Ändern Sie das Standardpasswort umgehend.** Erzeugen Sie dazu einen neuen
> bcrypt-Hash, z. B. mit
> `php -r "echo password_hash('IHR-NEUES-PASSWORT', PASSWORD_DEFAULT);"`, und
> tragen Sie ihn in die Tabelle `admin` (Spalte `password_hash`) ein.

## 4. Umstieg von der Version 2.0

Ein Update ist kein Ersetzen einzelner Dateien:

1. **Datenbank und Verzeichnis sichern.** Ohne Sicherung kein Update.
2. **Einstellungen notieren** aus der alten `inc/config.php`.
3. **Neue Dateien hochladen** und die alten überschreiben. Die Dateien
   `java/prototype.js`, `java/jQuery.js` und `java/jquery-1.4.2.min.js` werden
   nicht mehr gebraucht und sollten vom Server gelöscht werden.
4. **`install/` erneut aufrufen** und dieselben Datenbankdaten angeben.
   Vorhandene Tabellen bleiben erhalten, die neue Tabelle `admin` kommt hinzu.
5. **Datenbank auf `utf8mb4` umstellen**, falls sie noch auf `latin1` läuft.
   Neue Beiträge werden korrekt gespeichert; bereits falsch abgelegte Umlaute
   in alten Beiträgen werden dadurch nicht rückwirkend richtig. Wer den
   Altbestand nicht braucht, leert `feeds_post` und synchronisiert neu.
6. **`install/` löschen** und das Standardpasswort ändern.

## 5. Nutzung

1. **Neuen Feed eintragen** – Homepage und Feed-URL (RSS 2.0 oder Atom) angeben.
2. **Feeds synchronisieren** – ruft die Feeds ab und speichert neue Beiträge.
   Erst dieser Schritt füllt die Anzeige.
3. **Alle Feeds anzeigen** – zeigt die Beiträge (mit automatischem Nachladen
   beim Scrollen). Diese Seite ist auch ohne Anmeldung erreichbar.
4. **Feeds verwalten** – Feeds bearbeiten oder löschen; die Übersicht zeigt
   Zeitpunkt und Ergebnis des letzten Laufs.

In der kostenlosen Version stoßen Sie die Synchronisierung per Klick an. Ein
Cronjob kann sie nicht aufrufen: Seit Version 3.0 verlangt sie eine Anmeldung,
und ein zeitgesteuerter Abruf landet am Anmeldeformular. Automatisch
synchronisieren kann die Premium-Version (siehe Menüpunkt „Premium-Version").

**Feed ändern oder löschen:** Unter „Feed verwalten" stehen bei jedem Eintrag
die Links „Bearbeiten" und „Löschen". Gelöscht wird ohne weitere Rückfrage.
Die bereits abgerufenen Beiträge des Feeds verschwinden damit aus der Anzeige.

## 6. Sicherheit nach der Installation

- Standard-Admin-Passwort ändern (siehe oben). Der Zugang `admin`/`admin` ist
  öffentlich bekannt.
- `inc/config.php` enthält Zugangsdaten – per Server/`.htaccess` vor direktem
  Abruf schützen.
- Install-Verzeichnis entfernt halten, auch nach einem Update.
- Die Seite über `https` betreiben, sonst geht das Passwort im Klartext über die
  Leitung.

## 7. Installation per Docker (Entwicklung)

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

## 8. Fehlerbehebung

| Problem                          | Ursache / Lösung                              |
|----------------------------------|-----------------------------------------------|
| Weiterleitung auf `install/`     | `inc/config.php` fehlt – Installation ausführen|
| „Datenbankverbindung nicht möglich" | DB-Zugangsdaten in `inc/config.php` prüfen  |
| Umlaute falsch dargestellt       | Datenbank/Tabellen müssen `utf8mb4` sein       |
| Feeds werden nicht abgerufen     | `allow_url_fopen` aktivieren; Feed-Adresse im Browser prüfen |
| Ein Feed meldet dauerhaft „fehler" | Die Gegenstelle antwortet nicht oder weist automatische Abrufe ab. Feed-Adresse im Browser aufrufen und prüfen, ob dort XML ankommt. |
| Beim Scrollen wird nichts nachgeladen | JavaScript im Browser aktivieren           |
| Nach dem Anmelden erscheint wieder das Formular | Der Server kann keine Sitzungen speichern – Sitzungsverzeichnis von PHP prüfen |
| PHP-Version-Hinweis im Installer | PHP auf 8.5+ aktualisieren                     |
