<?php

/**
 * PHPUnit-Bootstrap.
 *
 * Wandelt jede PHP-Diagnose (Warning/Notice/Deprecation) in eine Exception um,
 * damit Tests bei PHP-8.5-Auffaelligkeiten der Anwendung sofort scheitern.
 *
 * App-Klassen werden hier bewusst NICHT geladen: Die Controller binden
 * `classes/parase.php` per `include` (ohne Guard) selbst ein. Ein Vorab-Laden
 * wuerde in isolierten Controller-Prozessen zu „Cannot redeclare class PARSE“
 * fuehren. Unit-Tests laden die benoetigten Dateien per `require_once` selbst.
 */

declare(strict_types=1);

error_reporting(E_ALL);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// Projektwurzel fuer Tests verfuegbar machen.
define('RSSG_ROOT', dirname(__DIR__));
