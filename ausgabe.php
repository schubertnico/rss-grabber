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

header("content-type: text/html; charset=UTF-8");

$ajax = (int)($_POST['ajax'] ?? 0);
// Divisor/Limit absichern: verhindert DivisionByZeroError und ungültige LIMITs.
$anz_anzeige = max(1, (int)$anz_anzeige);
$feeds=[];
$ausgabe='';
$lang_navigation_top=[];
$sql_select="SELECT url, id FROM `feeds`;";
$query = mysqli_query($link, $sql_select);

$sql_zusatz=[];
$i=0;
if($query instanceof mysqli_result && mysqli_num_rows($query)!=0){
    while($daten = mysqli_fetch_assoc($query)){
    	$feedId = (string)$daten['id'];
    	$feedUrl = (string)$daten['url'];
    	$feeds[$feedId]['url'] = $feedUrl;
    	$url=explode("/",str_replace(["http://","www."],"",$feedUrl));
    	$feeds[$feedId]['name'] = $url[0];
    	$sql_zusatz[$i]=$daten['id'];
		$i++;
    }
}
#var_dump(($_POST['ajax']!=''));
if($ajax!==0){
	$start=($anz_anzeige*($ajax+1));
	$sql_select='SELECT * FROM `feeds_post` where `feeds_id` in(\''.implode("','",array_values($sql_zusatz)).'\') ORDER BY `feeds_post`.`pubDate` DESC LIMIT '.(int)$start.', '.$anz_anzeige;

  $query = mysqli_query($link, $sql_select);
	if($query instanceof mysqli_result && mysqli_num_rows($query)!=0){
	    while($daten = mysqli_fetch_assoc($query)){
	    	$datenLink = (string)$daten['link'];
	    	$datenTitle = (string)$daten['title'];
	    	$datenFeedsId = (string)$daten['feeds_id'];
	    	$datenPubDate = (string)$daten['pubDate'];
	    	$datenDescription = (string)$daten['description'];
	    	$ausgabe .='<a href="'.$datenLink.'" target="_blank" class="beitrag_title" title="'.$datenTitle.'">'.$datenTitle.'</a><br><div class="beitrag_pubDate">Geschrieben von <a href="'.($feeds[$datenFeedsId]['url'] ?? '#').'" target="_blank">'.($feeds[$datenFeedsId]['name'] ?? 'unbekannt').'</a> am '.date_mysql2german($datenPubDate).'</div>';
	    	$ausgabe .='<div class="beitrag_description">'.limitch(strip_tags($datenDescription),$max_laege_description).'</div><div class="beitrag_link"><a href="'.$datenLink.'" target="_blank" class="beitrag_link">'.limitch($datenLink,95).'</a></div><br><br>';
	    }
	}
	echo $ausgabe;
	exit;
}


$sql_select='SELECT id FROM `feeds_post` where `feeds_id` in(\''.implode("','",array_values($sql_zusatz)).'\') ORDER BY `feeds_post`.`pubDate` DESC';
$query = mysqli_query($link, $sql_select);
$anz= $query instanceof mysqli_result ? (int)round((int)mysqli_num_rows($query)/$anz_anzeige) : 0;

$sql_select='SELECT * FROM `feeds_post` where `feeds_id` in(\''.implode("','",array_values($sql_zusatz)).'\') ORDER BY `feeds_post`.`pubDate` DESC LIMIT '.$anz_anzeige;
$query = mysqli_query($link, $sql_select);
if($query instanceof mysqli_result && mysqli_num_rows($query)!=0){
    while($daten = mysqli_fetch_assoc($query)){
    	$datenLink = (string)$daten['link'];
    	$datenTitle = (string)$daten['title'];
    	$datenFeedsId = (string)$daten['feeds_id'];
    	$datenPubDate = (string)$daten['pubDate'];
    	$datenDescription = (string)$daten['description'];
    	$ausgabe .='<a href="'.$datenLink.'" target="_blank" class="beitrag_title" title="'.$datenTitle.'">'.$datenTitle.'</a><br><div class="beitrag_pubDate">Geschrieben von <a href="'.($feeds[$datenFeedsId]['url'] ?? '#').'" target="_blank">'.($feeds[$datenFeedsId]['name'] ?? 'unbekannt').'</a> am '.date_mysql2german($datenPubDate).'</div>';
    	$ausgabe .='<div class="beitrag_description">'.limitch(strip_tags($datenDescription),$max_laege_description).'</div><div class="beitrag_link"><a href="'.$datenLink.'" target="_blank" class="beitrag_link">'.limitch($datenLink,95).'</a></div><br><br>';
    }
} else {
	$ausgabe .='Es wurden noch keine Feeds synchronisiert. Bitte klicken Sie als erstes auf Feeds verwalten um einen Feed hinzuzufügen. Anschließend können sie dann über Feeds synchronisieren die Daten der einzelnen Feeds abfragen.';
}
$lang['inhalt']=$ausgabe.'<div id="anz" style="display: none;">'.$anz.'</div>';


$template_navigation = new PARSE;
$template_navigation -> TEMPLATE ($lang_navigation_top,  __DIR__.'/tpl/navigation.html');
$lang['navigation']=$template_navigation ->TEMPLATE_RETURN();

$template = new PARSE;
$template -> TEMPLATE ($lang,  __DIR__.'/tpl/layout.html');
$template -> TEMPLATE_AUSGABE();