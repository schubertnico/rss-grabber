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
 * Geschützte Controller werden mit einer angemeldeten Session aufgerufen.
 * Voraussetzung: Ausführung im Web-Container (DB-Host "db" erreichbar).
 */
final class ControllerSmokeTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function controllerProvider(): array
    {
        return [
            // controller, benoetigtLogin
            'ausgabe.php'               => ['ausgabe.php', false],
            'premium-version.php'       => ['premium-version.php', false],
            'login.php'                 => ['login.php', false],
            'feeds_verwalten.php'       => ['feeds_verwalten.php', true],
            'feed_hinzufuegen.php'      => ['feed_hinzufuegen.php', true],
            'feed_bearbeiten.php'       => ['feed_bearbeiten.php', true],
            'feeds_synchronisieren.php' => ['feeds_synchronisieren.php', true],
        ];
    }

    #[DataProvider('controllerProvider')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testControllerRendertOhnePhpFehler(string $controller, bool $needsLogin): void
    {
        $host = getenv('RSSG_DB_HOST') ?: 'db';
        $probe = @mysqli_connect($host, 'rss_grabber', 'rss_grabber_secret', 'rss_grabber');
        if (!$probe instanceof \mysqli) {
            self::markTestSkipped('Datenbank (' . $host . ') nicht erreichbar.');
        }
        mysqli_close($probe);

        // Session vor jeglicher Ausgabe starten (auth.php/login.php starten sonst
        // mitten im Output-Buffer eine Session).
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if ($needsLogin) {
            $_SESSION['rssg_admin'] = 'admin';
        }

        $path = RSSG_ROOT . '/' . $controller;
        self::assertFileExists($path);

        ob_start();
        require $path;
        $html = (string)ob_get_clean();

        self::assertStringContainsString('</html>', $html, $controller . ' liefert keine vollständige Seite.');
        self::assertDoesNotMatchRegularExpression(
            '/(Fatal error|Parse error|Deprecated:|Warning:|Notice:|Uncaught)/',
            $html,
            $controller . ' enthält PHP-Fehlerausgabe.'
        );
    }
}
