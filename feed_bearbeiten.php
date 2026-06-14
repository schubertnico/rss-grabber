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

$postId = (int)($_POST['id'] ?? 0);
$postStatus = (int)($_POST['status'] ?? 0);
$postFeedUrl = is_string($_POST['feed_url'] ?? null) ? trim((string) $_POST['feed_url']) : '';
$postUrl = is_string($_POST['url'] ?? null) ? trim((string) $_POST['url']) : '';
$id = (int)($_GET['id'] ?? 0);

if(($_POST['senden'] ?? '') == 'speichern'){
	if (rssg_csrf_check($_POST['csrf'] ?? null) === false) {
		$lang_formular['meldung']= '<span style="color: red; ">Ungültiges Sicherheits-Token.</span>';
	} elseif ($postUrl !== '' && $postFeedUrl !== '' && $postId > 0 && in_array($postStatus, [1, 2], true)) {
		$ok = false;
		$stmt = mysqli_prepare($link, "UPDATE `feeds` SET `check` = ?, `feed_url` = ?, `url` = ? WHERE `id` = ? LIMIT 1");
		if ($stmt !== false) {
			mysqli_stmt_bind_param($stmt, 'issi', $postStatus, $postFeedUrl, $postUrl, $postId);
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
		}
		$lang_formular['meldung']= $ok
			? '<span style="color: green; ">Der Eintrag wurde geändert.</span>'
			: '<span style="color: red; ">Der Eintrag konnte nicht geändert werden!</span>';
		$id = $postId;
	}
}

$lang['inhalt']='keine Daten gefunden';
if($id > 0){
	$daten = null;
	$stmt = mysqli_prepare($link, "SELECT id, url, feed_url, `check` FROM `feeds` WHERE `id` = ? LIMIT 1");
	if ($stmt !== false) {
		mysqli_stmt_bind_param($stmt, 'i', $id);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		if ($res instanceof mysqli_result) {
			$daten = mysqli_fetch_assoc($res);
		}
		mysqli_stmt_close($stmt);
	}
	if (is_array($daten)) {
		$lang_formular['homepage']=rssg_e((string) $daten["url"]);
		$lang_formular['id']=(string)(int) $daten["id"];
		$lang_formular['feed_url']=rssg_e((string) $daten["feed_url"]);
		$lang_formular['status_1']=(((int)$daten["check"]==1)?'checked':'');
		$lang_formular['status_2']=(((int)$daten["check"]==2)?'checked':'');
		$lang_formular['csrf']=rssg_csrf_token();
		$template_formular = new PARSE;
		$template_formular -> TEMPLATE ($lang_formular,  __DIR__.'/tpl/feed_bearbeiten_form.html');
		$lang['inhalt']=$template_formular ->TEMPLATE_RETURN();
	}
}


$template_navigation = new PARSE;
$template_navigation -> TEMPLATE ($lang_navigation_top,  __DIR__.'/tpl/navigation.html');
$lang['navigation']=$template_navigation ->TEMPLATE_RETURN();

$template = new PARSE;
$template -> TEMPLATE ($lang,  __DIR__.'/tpl/layout.html');
$template -> TEMPLATE_AUSGABE();