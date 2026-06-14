<?php
/** @var mysqli $link */
/** @var int $anz_anzeige */
/** @var int $max_laege_description */
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
if (file_exists(__DIR__ . '/inc/config.php') === false) {
    if (headers_sent() === false) {
        header('Location: ./install/');
    }
    exit;
}
require_once(__DIR__ . '/inc/config.php');
require_once(__DIR__ . '/db.php');
require_once(__DIR__ . '/classes/function.php');
require_once(__DIR__ . '/classes/parase.php');
require_once(__DIR__ . '/classes/FeedRepository.php');

header("content-type: text/html; charset=UTF-8");

$lang = [];
$lang_navigation_top = [];
$repo = new FeedRepository($link);
$feeds = $repo->feedMap();
$feedIds = array_keys($feeds);

$perPage = max(1, (int)$anz_anzeige);
// AJAX-Nachladen wird am VORHANDENSEIN des ajax-Parameters erkannt – auch
// ajax=0 (erster Nachlade-Schritt) muss nur das Fragment liefern, nicht das
// gesamte Layout.
$isAjaxRequest = array_key_exists('ajax', $_POST);
$ajax = (int)($_POST['ajax'] ?? 0);

if ($isAjaxRequest) {
	$ausgabe = '';
	$offset = $perPage * ($ajax + 1);
	foreach ($repo->latestPosts($feedIds, $perPage, $offset) as $post) {
		$ausgabe .= rssg_render_feed_post($post, $feeds, $max_laege_description);
	}
	echo $ausgabe;
	exit;
}

$anz = (int)round($repo->countPosts($feedIds) / $perPage);

$ausgabe = '';
$posts = $repo->latestPosts($feedIds, $perPage, 0);
if ($posts === []) {
	$ausgabe = 'Es wurden noch keine Feeds synchronisiert. Bitte klicken Sie als erstes auf Feeds verwalten um einen Feed hinzuzufügen. Anschließend können sie dann über Feeds synchronisieren die Daten der einzelnen Feeds abfragen.';
} else {
	foreach ($posts as $post) {
		$ausgabe .= rssg_render_feed_post($post, $feeds, $max_laege_description);
	}
}
$lang['inhalt']=$ausgabe.'<div id="anz" style="display: none;">'.$anz.'</div>';


$template_navigation = new PARSE;
$template_navigation -> TEMPLATE ($lang_navigation_top,  __DIR__.'/tpl/navigation.html');
$lang['navigation']=$template_navigation ->TEMPLATE_RETURN();

$template = new PARSE;
$template -> TEMPLATE ($lang,  __DIR__.'/tpl/layout.html');
$template -> TEMPLATE_AUSGABE();