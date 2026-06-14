<?php
/** @var mysqli $link */
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
require_once(__DIR__ . '/inc/auth.php');
rssg_require_login();
$repo = new FeedRepository($link);
$lang=[];
$lang_formular=[];
$lang_navigation_top=[];
$lang_formular['meldung']='';
$postFeedUrl = is_string($_POST['feed_url'] ?? null) ? trim((string) $_POST['feed_url']) : '';
$postUrl = is_string($_POST['url'] ?? null) ? trim((string) $_POST['url']) : '';
if(($_POST['senden'] ?? '') == 'speichern'){
	if (rssg_csrf_check($_POST['csrf'] ?? null) === false) {
		$lang_formular['meldung']= '<span style="color: red; ">Ungültiges Sicherheits-Token.</span>';
	} elseif ($postUrl !== '' && $postFeedUrl !== '') {
		if ($repo->existsByFeedUrl($postFeedUrl)) {
			$lang_formular['meldung']= '<span style="color: red; ">Es ist schon ein Feed mit der Url: ' . rssg_e($postFeedUrl) . ' vorhanden!</span>';
		} else {
			$lang_formular['meldung']= $repo->add($postFeedUrl, $postUrl)
				? '<span style="color: green; ">Der Eintrag wurde erfolgreich gespeichert.</span>'
				: '<span style="color: red; ">Der Eintrag konnte nicht gespeichert werden!</span>';
		}
	}
}
$lang_formular['csrf'] = rssg_csrf_token();

$template_formular = new PARSE;
$template_formular -> TEMPLATE ($lang_formular,  __DIR__.'/tpl/feed_hinzufuegen_form.html');
$lang['inhalt']=$template_formular ->TEMPLATE_RETURN();


$template_navigation = new PARSE;
$template_navigation -> TEMPLATE ($lang_navigation_top,  __DIR__.'/tpl/navigation.html');
$lang['navigation']=$template_navigation ->TEMPLATE_RETURN();

$template = new PARSE;
$template -> TEMPLATE ($lang,  __DIR__.'/tpl/layout.html');
$template -> TEMPLATE_AUSGABE();