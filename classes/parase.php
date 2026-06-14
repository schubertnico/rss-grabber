<?php
/**
 * -----------------------------------------
 * RSS Grabber free v3.0 - 2026-06-14
 * -----------------------------------------
 * @copyright Copyright 2011, Schubertmedia/Nico Schubert
 * @link http://www.php-space.info/rss-grabber/ - Dokumentation und Informationen rund um das PHP Script.
 * @version free v3.0 (PHP 8.5)
 * @abstract
 * Das Script darf kostenlos verwendet werden. Es müssen aber alle Copyright Hinweise erhalten bleiben.
 * Für einen einmaligen Betrag von 9,95 EUR erhalten Sie die Premium-Version. In der Premium-Version sind keine
 * sichtbaren Copyright Hinweise mehr enthalten. Dadurch unterstützen Sie die Weiterentwicklung und würdigen diese Arbeit.
 */
class PARSE
{
    /**
     * @var string
     */
    public $ausgabe = '';

    /**
     * Lädt eine Template-Datei und ersetzt {PLATZHALTER} durch die Werte aus $lang.
     *
     * @param array<string, string> $lang
     */
    public function TEMPLATE(array $lang, string $file): bool
    {
        if (file_exists($file) === false) {
            throw new \RuntimeException('Template-Datei nicht gefunden: ' . $file);
        }
        // file_get_contents ist robust: bei leerer Datei liefert es '' statt
        // (wie fread($fp, 0)) einen fatalen ValueError unter PHP 8.
        $to_parse = file_get_contents($file);
        if ($to_parse === false) {
            throw new \RuntimeException('Template-Datei nicht lesbar: ' . $file);
        }
        preg_match_all("/{([0-9A-Z_]*)}/", $to_parse, $parse_vars);
        $stop = count($parse_vars[0]);
        for ($x = 0; $x < $stop; $x++) {
            $lang_ident = strtolower($parse_vars[1][$x]);
            if (!isset($lang[$lang_ident])) {
                $lang[$lang_ident] = '';
            }
            $to_parse = str_replace($parse_vars[0][$x], $lang[$lang_ident], $to_parse);
        }
        $this->ausgabe = $to_parse;
        return true;
    }

    public function TEMPLATE_RETURN(): string
    {
        return $this->ausgabe;
    }

    public function TEMPLATE_AUSGABE(): bool
    {
        if (headers_sent() === false) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo($this->TEMPLATE_RETURN());
        return true;
    }
}
