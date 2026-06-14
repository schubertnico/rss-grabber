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
$deleteId = (int)($_GET['id'] ?? 0);
$delete = (string)($_GET['delete'] ?? '');
$csrfToken = rssg_csrf_token();
$medlung='';
$feeds=[];
$lang_navigation_top=[];
$ausgabe='';
if($delete === '1' && $deleteId > 0){
	if (rssg_csrf_check($_GET['csrf'] ?? null) === false) {
		$medlung='<span class="fehler">Ungültiges Sicherheits-Token.</span>';
	} else {
		$ok = $repo->delete($deleteId);
		$medlung = $ok
			? '<span class="erfolgreich">Der Eintrag wurde gelöscht.</span>'
			: '<span class="fehler">Der Eintrag konnte nicht gelöscht.</span>';
	}
}

$alleFeeds = $repo->all();
if($alleFeeds !== []){
	$ausgabe .='<table>';
	$ausgabe .='<caption>Feeds verwalten</caption>';
	$ausgabe .='<thead>';
	$ausgabe .='	<tr>';
	$ausgabe .='   		<td colspan="2" class="einleitung">Hier können Sie die Feeds löschen bzw. bearbeiten, die Sie eingetragen haben.'.(($medlung !== '')?'<p>'.$medlung:'</p>').'</td>';
	$ausgabe .='	</tr>';
	$ausgabe .='	<tr>';
	$ausgabe .='		<th>Bezeichnung</th>';
	$ausgabe .='		<th>Daten</th>';
	$ausgabe .='	</tr>';
	$ausgabe .='</thead>';
	$ausgabe .='<tfoot>';
	$ausgabe .='	<tr>';
	$ausgabe .='   		<td colspan="2"></td>';
	$ausgabe .='	</tr>';
	$ausgabe .='</tfoot>';
	$ausgabe .='<tbody>';
    foreach($alleFeeds as $daten){
		$ausgabe .='<tr>';
		$ausgabe .='	<td class="line_b">Homepage:</td>';
		$ausgabe .='	<td class="line_d"><a href="'.rssg_safe_url((string)$daten['url']).'" target="_blank">'.rssg_e((string)$daten['url']).'</a></td>';
		$ausgabe .='</tr>';
		$ausgabe .='<tr>';
		$ausgabe .='	<td class="line_b">Feed URL:</td>';
		$ausgabe .='	<td class="line_d"><a href="'.rssg_safe_url((string)$daten['feed_url']).'" target="_blank">'.rssg_e((string)$daten['feed_url']).'</a></td>';
		$ausgabe .='</tr>';
		$ausgabe .='<tr>';
		$ausgabe .='	<td class="line_b">Feed aktiv:</td>';
		$ausgabe .='	<td class="line_d">'.(($daten['check']==1)?'Ja':'Nein').'</td>';
		$ausgabe .='</tr>';
		$ausgabe .='<tr>';
		$ausgabe .='	<td class="line_b">letzter Lauf:</td>';
		$ausgabe .='	<td class="line_d">'.(($daten['last_check']=='0')?'k.a.':date("d.m.Y \\u\\m H:i:s", ((int)$daten['last_check']-3600)).' Uhr').'</td>';
		$ausgabe .='</tr>';
		$ausgabe .='<tr>';
		$ausgabe .='	<td class="line_b">Status:</td>';
		$ausgabe .='	<td class="line_d">'.rssg_e(ucfirst((string)$daten['last_status'])).'</td>';
		$ausgabe .='</tr>';
		$ausgabe .='<tr>';
			$ausgabe .='	<td class="line" colspan="2"><img src="img/pfeil.jpg"><a href="'.rssg_e((string)($_SERVER['PHP_SELF'] ?? '')).'?delete=1&id='.(int)$daten['id'].'&csrf='.rssg_e($csrfToken).'">Löschen</a>, <a href="feed_bearbeiten.php?id='.(int)$daten['id'].'">Bearbeiten</a></td>';
			$ausgabe .='</tr>';

    }
    $ausgabe .='</table>';
}
$lang['inhalt']=$ausgabe;
$template_navigation = new PARSE;
$template_navigation -> TEMPLATE ($lang_navigation_top,  __DIR__.'/tpl/navigation.html');
$lang['navigation']=$template_navigation ->TEMPLATE_RETURN();

$template = new PARSE;
$template -> TEMPLATE ($lang,  __DIR__.'/tpl/layout.html');
$template -> TEMPLATE_AUSGABE();