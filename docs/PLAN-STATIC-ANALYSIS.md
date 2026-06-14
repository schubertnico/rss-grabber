# Umsetzungsplan – Statisches Analyse-Gate (PHPStan)

1. `composer require --dev phpstan/phpstan phpstan/phpstan-phpunit`.
2. `phpstan.neon`: Level 8, App-Dateien + `tests/`, `treatPhpDocTypesAsCertain: false`,
   `bootstrapFiles: tests/bootstrap.php`.
3. Lauf, reale Befunde beheben (config.php, login.php, logout.php, function.php).
4. Composer-Shortcut `composer analyse`.
5. Regression: PHPUnit + Playwright im Docker, `php-error.log` leer.
6. Doku (README/CHANGELOG/SPECS); Commit → Merge `main` → Push → Branch löschen.
