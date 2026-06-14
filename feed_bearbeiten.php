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
if (!isset($_POST['id'])) {
    $_POST['id']='';
}
if (!isset($_POST['status'])) {
    $_POST['status']='';
}
$_GET['id'] = isset($_GET['id']) ? sprintf("%d",$_GET['id']) : '0';
if($_POST['senden'] == 'speichern' && $_POST['url'] != '' && $_POST['feed_url'] != '' && $_POST['id'] != '' && $_POST['status'] != ''){
	$sql_update="UPDATE `feeds` SET `check` = '".mysqli_escape_string($link, (string) $_POST['status'])."', `feed_url` = '".mysqli_escape_string($link, (string) $_POST['feed_url'])."', `url`='".mysqli_escape_string($link, (string) $_POST['url'])."' WHERE `id` = '".mysqli_escape_string($link, (string) $_POST['id'])."' LIMIT 1;";
	if(@mysqli_query($link, $sql_update)){
		$lang_formular['meldung']= '<span style="color: green; ">Der Eintrag wurde geändert.</span>';
	} else {
		$lang_formular['meldung']= '<span style="color: red; ">Der Eintrag konnte nicht geändert werden!</span>';
	}
	$_GET['id']=$_POST['id'];

}
if($_GET['id']!='0'){
	$sql_select="SELECT * FROM `feeds` WHERE `id` = '".mysqli_escape_string($link, (string) $_GET['id'])."' LIMIT 1;";
	$query = @mysqli_query($link, $sql_select);
    if($query instanceof mysqli_result && mysqli_num_rows($query)!=0){
	    $daten = mysqli_fetch_assoc($query);
	    if ($daten !== null && $daten !== false) {
	   	$lang_formular['homepage']=(string) $daten["url"];
	   	$lang_formular['id']=(string) $daten["id"];
	   	$lang_formular['feed_url']=(string) $daten["feed_url"];
	   	$lang_formular['status_1']=(($daten["check"]==1)?'checked':'');
	   	$lang_formular['status_2']=(($daten["check"]==2)?'checked':'');
	  	$template_formular = new PARSE;
		$template_formular -> TEMPLATE ($lang_formular,  __DIR__.'/tpl/feed_bearbeiten_form.html');
		$lang['inhalt']=$template_formular ->TEMPLATE_RETURN();
	    } else {
		$lang['inhalt']='keine Daten gefunden';
	    }
    } else {
	$lang['inhalt']='keine Daten gefunden';
    }
} else {
	$lang['inhalt']='keine Daten gefunden';
}


$template_navigation = new PARSE;
$template_navigation -> TEMPLATE ($lang_navigation_top,  __DIR__.'/tpl/navigation.html');
$lang['navigation']=$template_navigation ->TEMPLATE_RETURN();

$template = new PARSE;
$template -> TEMPLATE ($lang,  __DIR__.'/tpl/layout.html');
$template -> TEMPLATE_AUSGABE();