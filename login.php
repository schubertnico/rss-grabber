<?php
/**
 * -----------------------------------------
 * RSS Grabber free v2.0 - Anmeldung
 * -----------------------------------------
 * @version free v2.0 (PHP 8.5)
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

// Bereits angemeldet -> direkt in die Verwaltung.
if (rssg_is_logged_in()) {
    if (headers_sent() === false) {
        header('Location: ./feeds_verwalten.php');
    }
    exit;
}

$lang = [];
$lang_formular = [];
$lang_navigation_top = [];
$lang_formular['meldung'] = '';
$username = '';

if (($_POST['senden'] ?? '') === 'login') {
    if (rssg_csrf_check($_POST['csrf'] ?? null) === false) {
        $lang_formular['meldung'] = '<span style="color: red;">Ungültiges Sicherheits-Token. Bitte erneut versuchen.</span>';
    } else {
        $username = is_string($_POST['username'] ?? null) ? trim((string)$_POST['username']) : '';
        $password = is_string($_POST['password'] ?? null) ? (string)$_POST['password'] : '';

        $hash = '';
        $stmt = mysqli_prepare($link, 'SELECT password_hash FROM `admin` WHERE `username` = ? LIMIT 1');
        if ($stmt !== false) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $hash);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }

        if (is_string($hash) && $hash !== '' && password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['rssg_admin'] = $username;
            if (headers_sent() === false) {
                header('Location: ./feeds_verwalten.php');
            }
            exit;
        }
        $lang_formular['meldung'] = '<span style="color: red;">Benutzername oder Passwort ist falsch.</span>';
    }
}

$lang_formular['csrf'] = rssg_csrf_token();
$lang_formular['username'] = rssg_e($username);

$template_formular = new PARSE;
$template_formular->TEMPLATE($lang_formular, __DIR__ . '/tpl/login_form.html');
$lang['inhalt'] = $template_formular->TEMPLATE_RETURN();

$template_navigation = new PARSE;
$template_navigation->TEMPLATE($lang_navigation_top, __DIR__ . '/tpl/navigation.html');
$lang['navigation'] = $template_navigation->TEMPLATE_RETURN();

$template = new PARSE;
$template->TEMPLATE($lang, __DIR__ . '/tpl/layout.html');
$template->TEMPLATE_AUSGABE();
