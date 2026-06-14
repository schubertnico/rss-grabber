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
require_once(__DIR__ . '/inc/auth.php');
rssg_require_login();
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
		$exists = false;
		$stmt = mysqli_prepare($link, "SELECT id FROM `feeds` WHERE `feed_url` = ? LIMIT 1");
		if ($stmt !== false) {
			mysqli_stmt_bind_param($stmt, 's', $postFeedUrl);
			mysqli_stmt_execute($stmt);
			mysqli_stmt_store_result($stmt);
			$exists = mysqli_stmt_num_rows($stmt) > 0;
			mysqli_stmt_close($stmt);
		}
		if ($exists === false) {
			$ok = false;
			$ins = mysqli_prepare($link, "INSERT INTO `feeds` (`feed_url`,`url`,`check`,`last_status`,`last_check`) VALUES (?, ?, 1, 'k.a.', 0)");
			if ($ins !== false) {
				mysqli_stmt_bind_param($ins, 'ss', $postFeedUrl, $postUrl);
				$ok = mysqli_stmt_execute($ins);
				mysqli_stmt_close($ins);
			}
			$lang_formular['meldung']= $ok
				? '<span style="color: green; ">Der Eintrag wurde erfolgreich gespeichert.</span>'
				: '<span style="color: red; ">Der Eintrag konnte nicht gespeichert werden!</span>';
		} else {
			$lang_formular['meldung']= '<span style="color: red; ">Es ist schon ein Feed mit der Url: ' . rssg_e($postFeedUrl) . ' vorhanden!</span>';
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