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
if (@file_exists('./inc/config.php')) {
    include_once(__DIR__ . "/inc/config.php");
} else {
	header ("Location:./install/");
}
include(__DIR__ . '/db.php');
include(__DIR__ . '/classes/function.php');
include(__DIR__ . '/classes/parase.php');
$lang=[];
$lang_formular=[];
$lang_navigation_top=[];
$lang_formular['meldung']='';
if (!isset($_POST['senden'])) {
    $_POST['senden']='';
}
if (!isset($_POST['url'])) {
    $_POST['url']='';
}
if (!isset($_POST['feed_url'])) {
    $_POST['feed_url']='';
}
if($_POST['senden'] == 'speichern' && $_POST['url'] != '' && $_POST['feed_url'] != ''){
	$sql_select="SELECT id FROM `feeds` WHERE `feed_url` = '".mysqli_escape_string($link, (string) $_POST['feed_url'])."' LIMIT 1;";
	$query = mysqli_query($link, $sql_select);
	if($query instanceof mysqli_result && mysqli_num_rows($query)==0){
		$sql_insert="INSERT INTO `feeds` (`feed_url`,`url`,`check`, `last_status`, `last_check` ) VALUES('".mysqli_escape_string($link, (string) $_POST['feed_url'])."', '".mysqli_escape_string($link, (string) $_POST['url'])."','1','k.a.',0);";
		if(@mysqli_query($link, $sql_insert)!=false){
			$lang_formular['meldung']= '<span style="color: green; ">Der Eintrag wurde erfolgreich gespeichert.</span>';
		} else {
			$lang_formular['meldung']= '<span style="color: red; ">Der Eintrag konnte nicht gespeichert werden!</span>';
		}
	} else {
		$lang_formular['meldung']= '<span style="color: red; ">Es ist schon ein Feed mit der Url: ' .htmlspecialchars((string) $_POST['feed_url']). ' vorhanden!</span>';
	}
}

$template_formular = new PARSE;
$template_formular -> TEMPLATE ($lang_formular,  __DIR__.'/tpl/feed_hinzufuegen_form.html');
$lang['inhalt']=$template_formular ->TEMPLATE_RETURN();


$template_navigation = new PARSE;
$template_navigation -> TEMPLATE ($lang_navigation_top,  __DIR__.'/tpl/navigation.html');
$lang['navigation']=$template_navigation ->TEMPLATE_RETURN();

$template = new PARSE;
$template -> TEMPLATE ($lang,  __DIR__.'/tpl/layout.html');
$template -> TEMPLATE_AUSGABE();