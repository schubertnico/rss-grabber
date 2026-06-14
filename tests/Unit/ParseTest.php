<?php

declare(strict_types=1);

namespace RssGrabber\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PARSE;
use RuntimeException;

require_once RSSG_ROOT . '/classes/parase.php';

final class ParseTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    private function makeTemplate(string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), 'tpl');
        self::assertIsString($file);
        file_put_contents($file, $content);
        $this->tempFiles[] = $file;
        return $file;
    }

    public function testPlatzhalterWirdErsetzt(): void
    {
        $file = $this->makeTemplate('Hallo {NAME}, willkommen!');
        $parser = new PARSE();
        self::assertTrue($parser->TEMPLATE(['name' => 'Welt'], $file));
        self::assertSame('Hallo Welt, willkommen!', $parser->TEMPLATE_RETURN());
    }

    public function testUmlautePlatzhalterBleibenIntakt(): void
    {
        $file = $this->makeTemplate('Grüße: {TEXT}');
        $parser = new PARSE();
        $parser->TEMPLATE(['text' => 'Schöne Grüße äöüß'], $file);
        self::assertSame('Grüße: Schöne Grüße äöüß', $parser->TEMPLATE_RETURN());
        self::assertTrue(mb_check_encoding($parser->TEMPLATE_RETURN(), 'UTF-8'));
    }

    public function testFehlenderPlatzhalterWirdLeer(): void
    {
        $file = $this->makeTemplate('A{FEHLT}B');
        $parser = new PARSE();
        $parser->TEMPLATE([], $file);
        self::assertSame('AB', $parser->TEMPLATE_RETURN());
    }

    public function testLeeresTemplateWirftKeinenFehler(): void
    {
        // Regression: fread($fp, 0) warf frueher einen ValueError. file_get_contents
        // liefert bei leerer Datei korrekt ''.
        $file = $this->makeTemplate('');
        $parser = new PARSE();
        self::assertTrue($parser->TEMPLATE(['x' => 'y'], $file));
        self::assertSame('', $parser->TEMPLATE_RETURN());
    }

    public function testTemplateReturnOhneLadenLiefertLeer(): void
    {
        $parser = new PARSE();
        self::assertSame('', $parser->TEMPLATE_RETURN());
    }

    public function testFehlendeDateiWirftException(): void
    {
        $parser = new PARSE();
        $this->expectException(RuntimeException::class);
        $parser->TEMPLATE([], sys_get_temp_dir() . '/gibt-es-nicht-' . uniqid() . '.html');
    }

    #[RunInSeparateProcess]
    public function testTemplateAusgabeEchotInhalt(): void
    {
        $file = $this->makeTemplate('Ausgabe {WERT}');
        $parser = new PARSE();
        $parser->TEMPLATE(['wert' => 'OK'], $file);
        $this->expectOutputString('Ausgabe OK');
        self::assertTrue($parser->TEMPLATE_AUSGABE());
    }
}
