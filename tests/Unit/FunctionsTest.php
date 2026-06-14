<?php

declare(strict_types=1);

namespace RssGrabber\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once RSSG_ROOT . '/classes/function.php';

final class FunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        date_default_timezone_set('Europe/Berlin');
    }

    #[DataProvider('limitchProvider')]
    public function testLimitch(?string $value, int $length, string $expected): void
    {
        self::assertSame($expected, limitch($value, $length));
    }

    /**
     * @return array<string, array{0: ?string, 1: int, 2: string}>
     */
    public static function limitchProvider(): array
    {
        return [
            'kuerzer als Grenze bleibt unveraendert' => ['abc', 10, 'abc'],
            'genau Grenze wird gekuerzt'             => ['Hello', 5, 'Hello...'],
            'laenger als Grenze wird gekuerzt'       => ['abcdef', 3, 'abc...'],
            'null ergibt leeren String'              => [null, 5, ''],
            'leerer String bleibt leer'              => ['', 5, ''],
            'stripslashes wird angewandt'            => ["O\\'Brien", 100, "O'Brien"],
            'multibyte zeichenweise gekuerzt'        => ['äöüäöü', 3, 'äöü...'],
        ];
    }

    public function testRenderFeedPostEscaptHtml(): void
    {
        $row = [
            'link' => 'https://example.com/a',
            'title' => '<script>alert(1)</script>',
            'feeds_id' => '1',
            'pubDate' => '2024-03-15 14:30:45',
            'description' => 'Beschreibung',
        ];
        $feeds = ['1' => ['url' => 'https://feed.example', 'name' => 'Mein Feed']];
        $html = rssg_render_feed_post($row, $feeds, 250);

        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)', $html);
        self::assertStringContainsString('class="beitrag_title"', $html);
        self::assertStringContainsString('Mein Feed', $html);
    }

    public function testRenderFeedPostNeutralisiertGefaehrlicheUrl(): void
    {
        $row = [
            'link' => 'javascript:alert(1)',
            'title' => 'Titel',
            'feeds_id' => '1',
            'pubDate' => '2024-03-15 14:30:45',
            'description' => 'x',
        ];
        $html = rssg_render_feed_post($row, [], 250);
        self::assertStringContainsString('href="#"', $html);
        // sicherheitsrelevant: kein ausführbares javascript:-Ziel im href
        self::assertStringNotContainsString('href="javascript:', $html);
        // unbekannter Feed -> Fallback-Name
        self::assertStringContainsString('unbekannt', $html);
    }

    public function testDateMysql2GermanFormatiertGueltigesDatum(): void
    {
        // 2024-03-15 ist ein Freitag.
        self::assertSame(
            'Freitag den 15.03.2024 um 14:30:45 Uhr',
            date_mysql2german('2024-03-15 14:30:45')
        );
    }

    #[DataProvider('robustDateProvider')]
    public function testDateMysql2GermanBleibtRobust(string $input): void
    {
        // Darf unter PHP 8.5 keine Warnung/Notice ausloesen (sonst wirft der
        // Bootstrap-Error-Handler) und muss ein wohlgeformtes Ergebnis liefern.
        $result = date_mysql2german($input);
        self::assertMatchesRegularExpression(
            '/^\p{L}+ den \d{2}\.\d{2}\.\d{4} um \d{2}:\d{2}:\d{2} Uhr$/u',
            $result
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function robustDateProvider(): array
    {
        return [
            'leerer String -> Default'      => [''],
            'Default-Wert'                  => ['0000-00-00 00:00:00'],
            'unvollstaendig ohne Zeit'      => ['2024-03-15'],
            'voellig ungueltig'             => ['kein-datum'],
        ];
    }
}
