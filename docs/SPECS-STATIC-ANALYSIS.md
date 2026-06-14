# Spezifikation – Statisches Analyse-Gate (PHPStan)

## Ziel

Ein statisches Analyse-Gate einführen, das Typ-/Logikfehler früh aufdeckt und
die bisherige Arbeit absichert – analog zum Eltern-Projekt (PHPStan Level 8).

## Anforderungen

1. **PHPStan** (mit `phpstan-phpunit`-Extension) als Dev-Abhängigkeit.
2. Konfiguration `phpstan.neon` auf **Level 8**; analysiert alle App-PHP-Dateien
   (`classes/`, `inc/`, `install/`, Controller, `login`/`logout`) und `tests/`.
3. Der Lauf ist **fehlerfrei** (`[OK] No errors`); gefundene reale Probleme
   werden behoben (keine pauschale Baseline).
4. Composer-Shortcut: `composer analyse`.

## Behobene Befunde (Level 8)
- `inc/config.php`: redundante `@var string`-Tags entfernt (Konflikt mit dem von
  PHPStan inferierten `non-falsy-string`).
- `login.php`: fehlende `@var mysqli $link`-Annotation ergänzt.
- `logout.php`: `setcookie((string)session_name(), …)` (Rückgabe `string|false`).
- `classes/function.php`: `rssg_render_feed_post()`-PHPDoc auf `array-key`
  (numerische Feed-IDs werden in PHP zu Integer-Keys).
- Defensive Laufzeit-Checks im Installer: `treatPhpDocTypesAsCertain: false`,
  damit sie nicht als „immer wahr/falsch" gemeldet werden.

## Akzeptanzkriterien
- [ ] `vendor/bin/phpstan analyse` → `[OK] No errors`.
- [ ] PHPUnit (37) und Playwright (16) weiterhin grün, `php-error.log` leer.

## Nicht-Ziele / Folgearbeit
- **Psalm** (errorLevel 1 wie im Eltern-Projekt) ist als Folgeschritt vorgesehen;
  die offizielle PHP-8.5-Unterstützung von Psalm wird zuvor geprüft, um
  Tooling-Friktion zu vermeiden.
