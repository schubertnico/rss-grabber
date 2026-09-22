<?php

declare(strict_types=1);

namespace RssGrabber\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

require_once RSSG_ROOT . '/classes/function.php';

/**
 * rssg_feed_eintraege() entscheidet, welche Elemente eines Feeds Beiträge sind.
 *
 * Anlass war eine Warnung beim Synchronisieren eines Atom-Feeds: Der Abruf lief
 * blind über $xml->channel->item und $xml->entry. Ein Atom-Feed hat kein
 * <channel>, der Zugriff ergibt null, und PHP 8 warnt bei foreach über null -
 * sichtbar für jeden Besucher, solange die Fehleranzeige an war.
 */
final class FeedEintraegeTest extends TestCase
{
    private const RSS = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel>
  <title>Beispiel</title>
  <item><title>Erster</title><link>https://example.org/1</link></item>
  <item><title>Zweiter</title><link>https://example.org/2</link></item>
</channel></rss>
XML;

    private const ATOM = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Beispiel</title>
  <entry><title>Eins</title></entry>
  <entry><title>Zwei</title></entry>
  <entry><title>Drei</title></entry>
</feed>
XML;

    public function testRssLiefertDieItemsAlsArtEins(): void
    {
        $eintraege = rssg_feed_eintraege(new SimpleXMLElement(self::RSS));

        self::assertCount(2, $eintraege);
        self::assertSame(['Erster', 'Zweiter'], array_map(
            static fn (array $e): string => (string) $e[0]->title,
            $eintraege
        ));
        self::assertSame([1, 1], array_column($eintraege, 1));
    }

    public function testAtomLiefertDieEntriesAlsArtZwei(): void
    {
        $eintraege = rssg_feed_eintraege(new SimpleXMLElement(self::ATOM));

        self::assertCount(3, $eintraege);
        self::assertSame([2, 2, 2], array_column($eintraege, 1));
    }

    /**
     * Der eigentliche Fehlerfall: Kein Aufruf darf eine Warnung auslösen.
     * PHPUnit wandelt Warnungen in dieser Konfiguration nicht in Fehler um,
     * deshalb wird sie hier ausdrücklich abgefangen.
     */
    public function testAtomLoestKeineWarnungAus(): void
    {
        $warnungen = [];
        set_error_handler(static function (int $nr, string $text) use (&$warnungen): bool {
            $warnungen[] = $text;
            return true;
        });
        try {
            $eintraege = rssg_feed_eintraege(new SimpleXMLElement(self::ATOM));
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $warnungen);
        self::assertCount(3, $eintraege);
    }

    public function testRssOhneItemsLiefertNichts(): void
    {
        $xml = new SimpleXMLElement('<rss version="2.0"><channel><title>leer</title></channel></rss>');

        self::assertSame([], rssg_feed_eintraege($xml));
    }

    public function testUnbekanntesFormatLiefertNichtsStattZuRaten(): void
    {
        $xml = new SimpleXMLElement('<html><body><p>Keine Feed-Adresse</p></body></html>');

        self::assertSame([], rssg_feed_eintraege($xml));
    }
}
