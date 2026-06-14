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
$deleteId = (int)($_GET['id'] ?? 0);
$delete = $_GET['delete'] ?? '';
$medlung='';
$feeds=[];
$lang_navigation_top=[];
$ausgabe='';
if($delete == 1){
	$sql_delete="DELETE FROM `feeds` WHERE `id` = '".mysqli_real_escape_string($link, (string)$deleteId)."' LIMIT 1;";
	if(@mysqli_query($link, $sql_delete)!=false){
		$medlung='<span class="erfolgreich">Der Eintrag wurde gelöscht.</span>';
	} else {
		$medlung='<span class="fehler">Der Eintrag konnte nicht gelöscht.</span>';
	}
}

$sql_select="SELECT * FROM `feeds`;";
$query = mysqli_query($link, $sql_select) OR die(mysqli_errno($link));
if (!$query instanceof mysqli_result) {
    die('Query failed');
}
$anz=mysqli_num_rows($query);
if($anz!=0){
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
	$i=0;
    while($daten = mysqli_fetch_assoc($query)){
    	$i++;
		$ausgabe .='<tr>';
		$ausgabe .='	<td class="line_b">Homepage:</td>';
		$ausgabe .='	<td class="line_d"><a href="'.$daten['url'].'" target="_blank">'.$daten['url'].'</a></td>';
		$ausgabe .='</tr>';
		$ausgabe .='<tr>';
		$ausgabe .='	<td class="line_b">Feed URL:</td>';
		$ausgabe .='	<td class="line_d"><a href="'.$daten['feed_url'].'" target="_blank">'.$daten['feed_url'].'</a></td>';
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
		$ausgabe .='	<td class="line_d">'.ucfirst((string)$daten['last_status']).'</td>';
		$ausgabe .='</tr>';
		$ausgabe .='<tr>';
			$ausgabe .='	<td class="line" colspan="2"><img src="img/pfeil.jpg"><a href="'.htmlspecialchars((string)($_SERVER['PHP_SELF'] ?? '')).'?delete=1&id='.$daten['id'].'">Löschen</a>, <a href="feed_bearbeiten.php?id='.$daten['id'].'">Bearbeiten</a></td>';
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