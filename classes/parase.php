<?php
/**
 * -----------------------------------------
 * RSS Grabber free v2.0 - 11.12.2022
 * -----------------------------------------
 * @copyright Copyright 2011, Schubertmedia/Nico Schubert
 * @link http://www.php-space.info/rss-grabber/ - Dokumentation und Informationen rund um das PHP Script.
 * @version free v2.0 (PHP8.1)
 * @abstract
 * Das Script darf kostenlos verwendet werden. Es müssen aber alle Copyright Hinweise erhalten bleiben.
 * Für einen einmaligen Betrag von 9,95 EUR erhalten Sie die Premium-Version. In der Premium-Version sind keine
 * sichtbaren Copyright Hinweise mehr enthalten. Daduch unterstutzen Sie die Weiterentwiklung und würdigen diese Arbeit.
 */
class PARSE
{
    /**
     * @var string
     */
    public $ausgabe;

    function TEMPLATE(array $lang, string $file): bool
    {
        if (@file_exists($file) === false) {
            echo 'TPL Datei: "' . $file . '" gibt es nicht!';
            exit;
        }
        $fp = @fopen($file, "r");
        $to_parse = fread($fp, filesize($file));
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

    function TEMPLATE_RETURN(): string
    {
        return $this->ausgabe ?? '';
    }

    function TEMPLATE_AUSGABE(): bool
    {
        header('Content-Type: text/html; charset=utf-8');
        echo($this->TEMPLATE_RETURN());
        return true;
    }
}