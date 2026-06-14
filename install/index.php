<?php
/**
 * -----------------------------------------
 * RSS Grabber free v2.0 - 11.12.2022
 * -----------------------------------------
 * @copyright Copyright 2011, Schubertmedia/Nico Schubert
 * @link http://www.php-space.info/rss-grabber/ - Dokumentation und Informationen rund um das PHP Script.
 * @version free v2.0 (PHP8.1)
 *
 * Das Script darf kostenlos verwendet werden. Es müssen aber alle Copyright Hinweise erhalten bleiben.
 * Für einen einmaligen Betrag von 9,95 EUR erhalten Sie die Premium-Version. In der Premium-Version sind keine
 * sichtbaren Copyright Hinweise mehr enthalten. Daduch unterstutzen Sie die Weiterentwiklung und würdigen diese Arbeit.
 */
// Seit PHP 8.1 wirft mysqli standardmäßig Exceptions. Im Installer ist ein
// fehlgeschlagener Verbindungsversuch (falsche DB-Daten) jedoch der erwartete
// Fehlerfall, der als Meldung angezeigt werden soll – daher Reporting abschalten.
mysqli_report(MYSQLI_REPORT_OFF);
$_POST['senden'] = (string)(int)($_POST['senden'] ?? 0);
$_POST['db_host'] = isset($_POST['db_host']) ? trim($_POST['db_host']) : '';
$_POST['db_datenbank'] = isset($_POST['db_datenbank']) ? trim($_POST['db_datenbank']) : '';
$_POST['db_user'] = isset($_POST['db_user']) ? trim($_POST['db_user']) : '';
$_POST['db_passwort'] = isset($_POST['db_passwort']) ? trim($_POST['db_passwort']) : '';
if (!isset($_POST['anzahl_grabber_pro_lauf'])) {
    $_POST['anzahl_grabber_pro_lauf']='';
} else {
    $_POST['anzahl_grabber_pro_lauf']=trim($_POST['anzahl_grabber_pro_lauf']);
}
$_POST['anz_anzeige'] = isset($_POST['anz_anzeige']) ? trim($_POST['anz_anzeige']) : '';
$_POST['max_laege_description'] = isset($_POST['max_laege_description']) ? trim($_POST['max_laege_description']) : '';
$_POST['iso_to_utf'] = isset($_POST['iso_to_utf']) ? trim($_POST['iso_to_utf']) : '';
if (!isset($erfolgreich)) {
    $erfolgreich='';
}
if (!isset($fehler)) {
    $fehler='';
}
if(version_compare(PHP_VERSION, '8.1', '<')){
	echo 'Es ist auf den Server die Php Version '.PHP_VERSION.' installiert. Um das Script verwenden zu k&ouml;nnen, ben&ouml;tigen Sie mind. die Version 8.1 oder h&ouml;her.';
	exit;
}
if($_POST['senden']==1){
	$config="<?php
	/**
	 * -----------------------------------------
	 * RSS Grabber free v2.0 - 11.12.2022
	 * -----------------------------------------
	 * @copyright Copyright 2011, Schubertmedia/Nico Schubert
	 * @link http://www.php-space.info/rss-grabber/ - Dokumentation und Informationen rund um das PHP Script.
	 * @version free v2.0 (PHP8.1)
	 * @abstract
	 * Das Script darf kostenlos verwendet werden. Es müssen aber alle Copyright Hinweise erhalten bleiben.
     * Für einen einmaligen Betrag von 9,95 EUR erhalten Sie die Premium Version. In der Premium Version sind keine
     * sichtbaren Copyright Hinweise mehr enthalten. Daduch unterstutzen Sie die Weiterentwiklung und würdigen diese Arbeit.
     */

	if(strpos('config.php',\$_SERVER['PHP_SELF'])) {
	    header('Location: ../index.php');
	    die();
	}
	/*
	 * Script Version
	 * */
	\$script_version='2.00';
	/*
	 * Bitte hinterlegen Sie hier die
	 * Mysql Datenbankdaten.
	 * */
	\$db_host='".$_POST['db_host']."';
	\$db_datenbank='".$_POST['db_datenbank']."';
	\$db_user='".$_POST['db_user']."';
	\$db_passwort='".$_POST['db_passwort']."';
	/**
	  * Die Anzahl, wie viele Feeds pro Aktualisierung geprüft werden soll
	  */
	 \$anzahl_grabber_pro_lauf='".$_POST['anzahl_grabber_pro_lauf']."';
	 /**
	  * Wie viele Einträge sollen auf der Ausgabeseite angezeigt werden?
	  */
	 \$anz_anzeige='".$_POST['anz_anzeige']."';
	 /**
	  * Maximalen Länge der Beschreibung von einen Blog Beitrag, die Länge wird mit der Anzahl der Zeichen berechnet
	  */
	 \$max_laege_description='".$_POST['max_laege_description']."';
	 /**
	  * Den Feed von ISO zu UTF 8 konvertieren
	  * 1 = ja, 2 = nein
	  */
	 \$iso_to_utf='".$_POST['iso_to_utf']."'";
	$config.=" ?>";
	if ($_POST['anzahl_grabber_pro_lauf']=='') {
         $fehler .="- Bitte geben Sie die Anzahl, wie viele Feeds pro Aktualisierung geprüft werden soll.<br>";
    }
    if ($_POST['anz_anzeige']=='') {
         $fehler .="- Bitte geben Sie wie viele Einträge sollen auf der Ausgabeseite angezeigt werden.<br>";
    }
    if ($_POST['max_laege_description']=='') {
         $fehler .="- Bitte geben Sie die maximale Länge der Beschreibung von einem Blog Beitrag an.<br>";
    }
    if ($_POST['iso_to_utf']=='') {
         $fehler .="- Bitte geben Sie, ob die Feeds von ISO zu UTF 8 konvertiert werden sollen.<br>";
    }
    if ($_POST['db_host']=='') {
         $fehler .="- Bitte geben Sie den Host von der Datenbank an.<br>";
    }
    if ($_POST['db_user']=='') {
         $fehler .="- Bitte geben Sie den Username von der Datenbank an.<br>";
    }
    if ($_POST['db_passwort']=='') {
         $fehler .="- Bitte geben Sie das Passwort von der Datenbank an.<br>";
    }
    if ($_POST['db_datenbank']=='') {
         $fehler .="- Bitte geben Sie den Namen der Datenbank von der Datenbank an.<br>";
    }
    if($fehler==""){
    	$link = @mysqli_connect($_POST['db_host'], $_POST['db_user'], $_POST['db_passwort'],$_POST['db_datenbank']);
        if (!$link) {
            $fehler .='- Es konnte keine Verbindung zum Datenbankserver hergestellt werden.<br>';
        } else {
	        mysqli_set_charset($link, 'utf8mb4');
	        $db_selected = @mysqli_select_db($link, $_POST['db_datenbank']);
	        if (!$db_selected) {
	            $fehler .='- Es konnte keine Verbindung zu der Datenbank hergestellt werden.<br>';
	        } else {
		        $sql="CREATE TABLE IF NOT EXISTS `feeds` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `feed_url` varchar(255) NOT NULL,
                  `url` varchar(255) NOT NULL,
                  `check` int(2) NOT NULL,
                  `last_check` int(20) NOT NULL,
                  `last_status` varchar(15) NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;";
				                $result = @mysqli_query($link, $sql);
				                if (!$result) {
				                   $fehler .='- Es konnte die Datenbank Tabelle "feeds" nicht angelegt werden.<br>';
				                }
				                $sql="CREATE TABLE IF NOT EXISTS `feeds_post` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `feeds_id` int(11) NOT NULL,
                  `pubDate` datetime NOT NULL,
                  `link` varchar(255) NOT NULL,
                  `title` varchar(255) NOT NULL,
                  `description` text NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;";
				$result = @mysqli_query($link, $sql);
				if (!$result) {
				   $fehler .='- Es konnte die Datenbank Tabelle "feeds_post" nicht angelegt werden.<br>';
				}
				$sql="CREATE TABLE IF NOT EXISTS `admin` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `username` varchar(64) NOT NULL,
                  `password_hash` varchar(255) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `username` (`username`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
				$result = @mysqli_query($link, $sql);
				if (!$result) {
				   $fehler .='- Es konnte die Datenbank Tabelle "admin" nicht angelegt werden.<br>';
				}
				// Default-Admin (admin / admin) anlegen, falls noch keiner existiert.
				$adminHash = '$2y$12$u7JQc1MKJTjjJBY7e6Y61uWg4Sy4MxxvFKpnpen1.mlUQ1PaINhTm';
				@mysqli_query($link, "INSERT INTO `admin` (`username`,`password_hash`) SELECT 'admin','".$adminHash."' WHERE NOT EXISTS (SELECT 1 FROM `admin` WHERE `username`='admin');");
				$sql_delete="DELETE FROM `feeds`;";
				@mysqli_query($link, $sql_delete);
				$sql="INSERT INTO `feeds` (`id`, `feed_url`, `url`, `check`, `last_check`, `last_status`) VALUES
				(1, 'https://www.php-space.info/feed.xml', 'https://www.php-space.info/php/space/news.php', 1, 0, ''),
				(2, 'https://www.php-space.info/script_feed.xml', 'https://www.php-space.info/scripte/', 1, 0, ''),
				(3, 'https://www.php-space.info/tutorial_feed.xml', 'https://www.php-space.info/php-tutorials/', 1, 0, '');";
				$result = @mysqli_query($link, $sql);
				if (!$result) {
				   $fehler .='- Es konnten keine Eintr&auml;ge in der Datenbank Tabelle "feeds" angelegt werden.<br>';
				}
	        }
        }
    }
	if($fehler==""){
		if (is_writable('../inc/')) {
		   $handle = fopen('../inc/config.php', "w");
		   if ($handle === false) {
		        $fehler.= "- Es konnte die config.php Datei nicht erstellen werden. <br>";
		   } else {
		       if (!fwrite($handle, $config))    {
		            $fehler.= "- Es konnte die config.php Datei nicht erstellen werden. <br>";
		       }
		       $erfolgreich = "Die Installation ist fertig. Bitte l&ouml;schen Sie das Verzeichnis \"install\" auf den Server. Danach k&ouml;nnen Sie <a href=\"../\">hier</a> alle Feeds verwalten. Bitte richten Sie noch einen Passwortschutz für das Verzeichnis ein, da ansonsten jeder Zugriff auf Ihre Feeds hat.<br>";
		       fclose($handle);
		   }
		} else {
		   $fehler.= "- Es konnte die config.php Datei nicht erstellen werden. <br>";
		}
	}
}
if($fehler == "" && @file_exists('../inc/config.php')){
   $fehler.= "- Es gibt schon die Datei: \"config.php\". Beachten Sie beim Ausführen der Installationsroutine, dass diese Datei überschrieben wird! <br>";
}
$dir=$_SERVER["DOCUMENT_ROOT"].dirname ($_SERVER['PHP_SELF']).'/';
$dir=str_replace("/install/","",$dir);
$version = phpversion();

$ausgabe = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
$ausgabe.= '<html xmlns="http://www.w3.org/1999/xhtml">';
$ausgabe.= '<head>';
$ausgabe.= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
$ausgabe.= '<title>Installationsroutine von RSS Grabber free v2.0</title>';
$ausgabe.= '<link rel="stylesheet" href="../css/styles.css" type="text/css" media="screen" />';
$ausgabe.= '</head>';
$ausgabe.= '<body>';
if ($erfolgreich!='' && version_compare(PHP_VERSION,'8.1')>=0) {
    $ausgabe.= $erfolgreich;
} elseif (version_compare(PHP_VERSION,'8.1')>=0) {
    $ausgabe.= '<form method="POST" action="index.php">';
    $ausgabe.= '<input type="hidden" name="senden"  value="1">';
    $ausgabe.= '<table class="layout">';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td colspan="2"><b>Installationsroutine von RSS Grabber free v2.0</b></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td colspan="2">Bitte f&uuml;llen Sie das Formular vollst&auml;ndig aus.</td>';
    $ausgabe.= '	</tr>';
    if($fehler!=''){
		$ausgabe.= '	<tr>';
		$ausgabe.= '		<td colspan="2"><br><span style="color: red; ">' .$fehler. '</span></td>';
		$ausgabe.= '	</tr>';
	}
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td colspan="2"><br><b>Datenbank Daten</b></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td colspan="2">Bitte geben Sie hier die Datenbankdaten ein, die Datenbankdaten erhalten Sie von Ihren Webhoster. Wenn die Datenbank auf den gleichen Server installiert ist, wo das Skript l&auml;uft, m&uuml;ssen sie "<i>localhost</i>" als Host angeben.</td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td>Host:</td>';
    $ausgabe.= '		<td><input type="text" name="db_host" '.(($_POST['db_host']!='')?'value="'.$_POST['db_host'].'"':'value="localhost"').' ></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td>Name der Datenbank:</td>';
    $ausgabe.= '		<td><input type="text" name="db_datenbank" '.(($_POST['db_datenbank']!='')?'value="'.$_POST['db_datenbank'].'"':'').'></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td>Username:</td>';
    $ausgabe.= '		<td><input type="text" name="db_user" '.(($_POST['db_user']!='')?'value="'.$_POST['db_user'].'"':'').'></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td>Passwort:</td>';
    $ausgabe.= '		<td><input type="password" name="db_passwort" '.(($_POST['db_passwort']!='')?'value="'.$_POST['db_passwort'].'"':'').'></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td colspan="2"><br><b>Einstellungsdaten</b></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td>Die Anzahl, wie viele Feeds pro Aktualisierung geprüft werden soll:</td>';
    $ausgabe.= '		<td><input type="text" name="anzahl_grabber_pro_lauf" '.(($_POST['anzahl_grabber_pro_lauf']!='')?'value="'.$_POST['anzahl_grabber_pro_lauf'].'"':'value="10"').'></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td>Wie viele Einträge sollen auf der Ausgabeseite angezeigt werden?</td>';
    $ausgabe.= '		<td><input type="text" name="anz_anzeige" '.(($_POST['anz_anzeige']!='')?'value="'.$_POST['anz_anzeige'].'"':'value="25"').'></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td>Maximalen Länge der Beschreibung von einem Blog Beitrag</td>';
    $ausgabe.= '		<td><input type="text" name="max_laege_description" '.(($_POST['max_laege_description']!='')?'value="'.$_POST['max_laege_description'].'"':'value="250"').'></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td>Den Feed von ISO zu UTF 8 konvertieren? (1 = ja, 2 = nein)</td>';
    $ausgabe.= '		<td><input type="text" name="iso_to_utf" '.(($_POST['iso_to_utf']!='')?'value="'.$_POST['iso_to_utf'].'"':'value="1"').'></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '	<tr>';
    $ausgabe.= '		<td colspan="2"><input type="submit" value="Installation fertigstellen" name="submit"><br><br></td>';
    $ausgabe.= '	</tr>';
    $ausgabe.= '</table>';
    $ausgabe.= '';
    $ausgabe.= '</form>';
} else {
    echo '<p>Die PHP-Version muss mind. 8.1 sein. Sie verwenden die PHP-Version: '.PHP_VERSION.'</p><p>Bitte aktualisieren Sie die PHP-Version auf Ihren Speicherplatz.</p>';
}
$ausgabe.= '</body>';
$ausgabe.= '</html>';
header('Content-Type: text/html; charset=utf-8');
echo $ausgabe;