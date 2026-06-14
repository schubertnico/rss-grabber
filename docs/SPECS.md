# Spezifikation – RSS Grabber Modernisierung (PHP 8.5 + UTF-8)

## Ziel

Der RSS Grabber (free v2.0) wird auf **PHP 8.5** und **durchgängiges UTF-8**
angepasst. Das Verhalten der Anwendung bleibt aus Anwendersicht gleich; intern
werden Zeichenkodierung, PHP-8.5-Kompatibilität und Testbarkeit hergestellt.

## Funktionaler Umfang (unverändert)

Der Grabber liest RSS-2.0- und Atom-Feeds aus, speichert deren Beiträge in einer
MySQL-Datenbank und zeigt sie an. Verwaltete Seiten:

| Seite                       | Zweck                                            |
|-----------------------------|--------------------------------------------------|
| `index.php`                 | Weiterleitung auf `ausgabe.php` bzw. `install/`  |
| `ausgabe.php`               | Anzeige aller Beiträge (inkl. AJAX-Endless-Scroll)|
| `feeds_verwalten.php`       | Feeds auflisten / löschen                        |
| `feed_hinzufuegen.php`      | Feed anlegen                                     |
| `feed_bearbeiten.php`       | Feed bearbeiten                                  |
| `feeds_synchronisieren.php` | Synchronisierung starten (UI)                    |
| `graber_ajax.php`           | Synchronisierung ausführen (AJAX-Backend)        |
| `premium-version.php`       | Hinweisseite                                     |
| `install/index.php`         | Erstinstallation (Tabellen + Konfiguration)      |

## Anforderungen

### A. UTF-8 / Umlaute (äöüß)

1. **Datenbank** speichert Inhalte als `utf8mb4` (Tabellen `feeds`, `feeds_post`).
2. **Verbindung** setzt explizit `utf8mb4` (`mysqli_set_charset`).
3. **Keine verlustbehaftete Transkodierung** mehr: die bisherige Wandlung
   `UTF-8 → ISO-8859-1//TRANSLIT` in `addItem()` wird entfernt. Feed-Inhalte
   werden so gespeichert, wie SimpleXML sie liefert (UTF-8).
4. **HTTP-Header und Templates** liefern `charset=utf-8` (bereits vorhanden,
   wird verifiziert).
5. Umlaute werden im Quellcode immer als `ä ö ü ß` geschrieben, nie als
   ASCII-Ersatz oder HTML-Entity-Hack.

### B. PHP 8.5

1. Kein Code löst unter PHP 8.5 **Deprecation-, Warning-, Notice- oder
   Fatal-Meldungen** aus.
2. Keine impliziten Annahmen auf nicht vorhandene Array-Keys / SimpleXML-Knoten.
3. Zahl-Parameter (z. B. `LIMIT`) werden typsicher als `int` behandelt.
4. Konfiguration ist umgebungsabhängig (Docker/Test) über Umgebungsvariablen
   überschreibbar, ohne das Verhalten der Produktiv-Installation zu ändern.

### C. Tests & Qualität

1. **PHPUnit** mit **≥ 80 % Line-Coverage** über die Kernlogik in `classes/`
   (Funktionen + `PARSE`-Klasse). Begründung der Scope-Wahl siehe unten.
2. **PHP-Smoke-Tests** je Controller (isolierter Prozess): Aufruf erzeugt
   **keinerlei** PHP-Fehlerausgabe (Warning/Notice/Deprecation/Fatal).
3. **Playwright-E2E** im laufenden Docker: jede Seite liefert HTTP **200**
   (keine 404/500), enthält keinen sichtbaren PHP-Fehlertext, Navigation und
   CRUD-Fluss (Feed anlegen → bearbeiten → löschen) funktionieren, Umlaute
   werden korrekt dargestellt.

### Coverage-Scope (Begründung)

Die Controller sind prozedural und beenden sich teils per `header()`/`exit`.
Sie eignen sich nicht für isolierte Unit-Coverage, werden aber durch
PHP-Smoke-Tests (Fehlerfreiheit) und Playwright-E2E (HTTP-Verhalten,
Darstellung) vollständig abgedeckt. Die messbare PHPUnit-Coverage bezieht sich
daher auf `classes/` – die testbare Geschäftslogik – gemäß Testpyramide.

## Nicht-Ziele

- Keine funktionale Erweiterung des Grabbers.
- Kein Redesign des Frontends.
- Keine Änderung an der Premium-Logik.

## Akzeptanzkriterien

- [ ] Alle Tabellen/Verbindungen sind `utf8mb4`; gespeicherte Umlaute erscheinen
      korrekt (kein Mojibake) in `ausgabe.php`.
- [ ] PHPUnit läuft grün, Coverage `classes/` ≥ 80 %.
- [ ] Controller-Smoke-Tests grün (kein PHP-Fehler-Output).
- [ ] Playwright-E2E grün: keine 404/500, keine PHP-Fehler im HTML.
- [ ] `php-error.log` bleibt nach Durchlauf aller Seiten leer.
