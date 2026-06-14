<?php

declare(strict_types=1);

namespace RssGrabber\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

/**
 * Smoke-Tests: jeder Controller wird in einem isolierten Prozess eingebunden
 * und gerendert. Der Bootstrap-Error-Handler wandelt jede PHP-Diagnose
 * (Warning/Notice/Deprecation) in eine Exception – die Tests scheitern also,
 * sobald ein Controller unter PHP 8.5 irgendeinen Fehler erzeugt.
 *
 * Voraussetzung: Ausfuehrung im Web-Container (DB-Host "db" erreichbar).
 */
final class ControllerSmokeTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function controllerProvider(): array
    {
        return [
            'ausgabe.php'               => ['ausgabe.php'],
            'feeds_verwalten.php'       => ['feeds_verwalten.php'],
            'feed_hinzufuegen.php'      => ['feed_hinzufuegen.php'],
            'feed_bearbeiten.php'       => ['feed_bearbeiten.php'],
            'feeds_synchronisieren.php' => ['feeds_synchronisieren.php'],
            'premium-version.php'       => ['premium-version.php'],
        ];
    }

    #[DataProvider('controllerProvider')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testControllerRendertOhnePhpFehler(string $controller): void
    {
        $host = getenv('RSSG_DB_HOST') ?: 'db';
        $probe = @mysqli_connect($host, 'rss_grabber', 'rss_grabber_secret', 'rss_grabber');
        if (!$probe instanceof \mysqli) {
            self::markTestSkipped('Datenbank (' . $host . ') nicht erreichbar.');
        }
        mysqli_close($probe);

        $path = RSSG_ROOT . '/' . $controller;
        self::assertFileExists($path);

        ob_start();
        require $path;
        $html = (string)ob_get_clean();

        // Vollstaendige Seite gerendert (Layout vorhanden).
        self::assertStringContainsString('</html>', $html, $controller . ' liefert keine vollstaendige Seite.');
        // Kein PHP-Fehlertext in der Ausgabe.
        self::assertDoesNotMatchRegularExpression(
            '/(Fatal error|Parse error|Deprecated:|Warning:|Notice:|Uncaught)/',
            $html,
            $controller . ' enthaelt PHP-Fehlerausgabe.'
        );
    }
}
